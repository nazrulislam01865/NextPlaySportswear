<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\PersonalizationTemplate;
use App\Models\Product;
use App\Models\User;
use App\Support\AdminRbac;
use App\Support\ProductRoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonalizationTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_wizard_creates_leaf_category_template_and_publishes_configured_fields(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $root = $this->category('Headwear');
        $leaf = $this->category('Custom Caps', $root);

        $this->actingAs($admin, 'admin')->get(route('admin.personalization-templates.index'))->assertOk();
        $this->actingAs($admin, 'admin')->post(route('admin.personalization-templates.store'), $this->basic($leaf))
            ->assertRedirect();

        $template = PersonalizationTemplate::query()->firstOrFail();
        $this->assertFalse($template->is_published);
        $this->assertSame($leaf->id, $template->category_id);

        $this->actingAs($admin, 'admin')->put(route('admin.personalization-templates.fields.update', $template), [
            'fields' => [
                ['key' => 'custom_text', 'label' => 'Custom Text', 'type' => 'text', 'placeholder' => 'Enter text', 'max_length' => 20, 'required' => '1', 'enabled' => '1'],
                ['key' => 'custom_number', 'label' => 'Custom Number', 'type' => 'number', 'placeholder' => 'Enter number', 'max_length' => 4, 'required' => '0', 'enabled' => '1'],
            ],
        ])->assertRedirect(route('admin.personalization-templates.preview', $template));
        $this->actingAs($admin, 'admin')->post(route('admin.personalization-templates.publish', $template))
            ->assertRedirect(route('admin.personalization-templates.index'));

        $template->refresh();
        $this->assertTrue($template->is_published);
        $this->assertCount(2, $template->fields);
        $this->assertSame('custom_number', $template->fields[1]['key']);
        // Regression: the index must compile and render populated template rows,
        // including their conditional Preview and Delete actions.
        $this->actingAs($admin, 'admin')
            ->get(route('admin.personalization-templates.index'))
            ->assertOk()
            ->assertSee('Custom Text &amp; Number (Caps)', false)
            ->assertSee('1.40 Personalization Templates');


        $product = new Product(['jersey_roster_enabled' => true, 'jersey_roster_optional' => false]);
        $product->setRelation('personalizationTemplate', $template);
        $roster = ProductRoster::forProduct($product);
        $this->assertSame('Custom Text & Number', $roster['title']);
        $this->assertSame('Use the same text & number for all items', $roster['shared_entry_label']);
        $this->assertSame('Custom Text', $roster['fields'][0]['label']);
    }

    public function test_multiple_selected_templates_keep_distinct_customer_section_titles_and_field_keys(): void
    {
        $product = new Product(['jersey_roster_enabled' => true, 'jersey_roster_optional' => false]);
        $text = new PersonalizationTemplate([
            'section_title' => 'Cap Text', 'name' => 'Text', 'is_active' => true, 'is_published' => true,
            'shared_entry_enabled' => true,
            'fields' => [['key' => 'name', 'label' => 'Text', 'enabled' => true, 'type' => 'text']],
        ]);
        $text->id = 12;
        $number = new PersonalizationTemplate([
            'section_title' => 'Custom Number', 'name' => 'Number', 'is_active' => true, 'is_published' => true,
            'shared_entry_enabled' => false,
            'fields' => [['key' => 'name', 'label' => 'Number', 'enabled' => true, 'type' => 'number']],
        ]);
        $number->id = 19;
        $product->setRelation('personalizationTemplates', collect([$text, $number]));
        $settings = ProductRoster::forProduct($product);

        $this->assertSame('Item Personalization', $settings['title']);
        $this->assertSame(['Cap Text', 'Custom Number'], array_column($settings['sections'], 'title'));
        $this->assertSame(['t12__name', 't19__name'], array_column($settings['fields'], 'key'));
        $this->assertFalse($settings['shared_entry_enabled']);
    }

    public function test_product_template_picker_shows_master_data_without_product_category_selection(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $caps = $this->category('Custom Caps');
        $jerseys = $this->category('Basketball Jerseys');

        PersonalizationTemplate::query()->create($this->basic($caps) + [
            'is_active' => true, 'is_published' => true,
            'fields' => [['key' => 'cap_text', 'label' => 'Custom Text', 'type' => 'text',
                'max_length' => 30, 'required' => true, 'enabled' => true]],
        ]);
        PersonalizationTemplate::query()->create(array_merge($this->basic($jerseys), [
            'name' => 'Jersey Initials', 'section_title' => 'Jersey Text',
            'is_active' => true, 'is_published' => false,
            'fields' => [['key' => 'initials', 'label' => 'Initials', 'type' => 'text',
                'max_length' => 8, 'required' => false, 'enabled' => true]],
        ]));

        // Both category-organized templates must be in the same picker even
        // though a new product has not yet selected its own category.
        $this->actingAs($admin, 'admin')->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('Add Personalization Template')
            ->assertSee('All template categories')
            ->assertSee('Jersey Initials')
            ->assertSee('Custom Text')
            ->assertDontSee('Choose a product category in Basics to see its templates.');
    }

    public function test_personalization_form_and_filters_use_searchable_controls_and_field_errors(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $leaf = $this->category('Custom Caps');
        $this->actingAs($admin, 'admin')->get(route('admin.personalization-templates.create'))
            ->assertOk()->assertSee('Search categories...')->assertSee('pt-select-trigger')
            ->assertDontSee('Internal reusable template name');
        $this->actingAs($admin, 'admin')->get(route('admin.personalization-templates.index'))
            ->assertOk()->assertSee('Clear filters')->assertSee('Search by template name or field...');
        $this->actingAs($admin, 'admin')->from(route('admin.personalization-templates.create'))
            ->post(route('admin.personalization-templates.store'), array_merge($this->basic($leaf), ['category_id' => '', 'name' => '']))
            ->assertSessionHasErrors(['category_id', 'name']);
        $this->assertSame(0, PersonalizationTemplate::query()->count());
    }

    public function test_parent_category_cannot_be_used_and_duplicate_field_keys_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $parent = $this->category('Sports Uniforms');
        $leaf = $this->category('Basketball Jerseys', $parent);

        $this->actingAs($admin, 'admin')->post(route('admin.personalization-templates.store'), $this->basic($parent))
            ->assertSessionHasErrors('category_id');
        $template = PersonalizationTemplate::query()->create($this->basic($leaf) + ['is_published' => false, 'fields' => []]);
        $this->actingAs($admin, 'admin')->put(route('admin.personalization-templates.fields.update', $template), [
            'fields' => [
                ['key' => 'name', 'label' => 'Name', 'type' => 'text', 'max_length' => 10, 'required' => 0, 'enabled' => 1],
                ['key' => 'name', 'label' => 'Name 2', 'type' => 'text', 'max_length' => 10, 'required' => 0, 'enabled' => 1],
            ],
        ])->assertSessionHasErrors('fields');
        $this->assertSame([], $template->fresh()->fields);
    }

    public function test_unassigned_template_draft_can_be_copied_and_view_only_user_cannot_publish(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $category = $this->category('Headwear');
        $template = PersonalizationTemplate::query()->create($this->basic($category) + [
            'is_published' => false,
            'fields' => [['key' => 'text', 'label' => 'Text', 'type' => 'text', 'max_length' => 10, 'required' => false, 'enabled' => true]],
        ]);

        $this->actingAs($admin, 'admin')->post(route('admin.personalization-templates.duplicate', $template))
            ->assertRedirect();
        $this->assertSame(2, PersonalizationTemplate::query()->count());
        $this->assertFalse(PersonalizationTemplate::query()->latest('id')->firstOrFail()->is_published);

        $viewer = User::factory()->create(['role' => 'catalog_manager', 'is_active' => true]);
        $role = \App\Models\AdminRole::query()->where('slug', 'catalog_manager')->firstOrFail();
        $role->permissions()->detach(\App\Models\AdminPermission::query()->where('key', 'customization.manage')->value('id'));
        $this->actingAs($viewer, 'admin')->post(route('admin.personalization-templates.publish', $template))
            ->assertForbidden();
    }

    private function basic(Category $category): array
    {
        return [
            'category_id' => $category->id, 'name' => 'Custom Text & Number (Caps)',
            'section_title' => 'Custom Text & Number',
            'description' => 'Add custom text and a number to your item.',
            'shared_entry_label' => 'Use the same text & number for all items',
            'shared_entry_enabled' => true, 'is_active' => true,
        ];
    }

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::query()->create([
            'parent_id' => $parent?->id, 'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(8)),
            'display_type' => 'collection', 'category_type' => 'standard',
            'page_template' => 'product_grid', 'status' => 'active',
            'is_active' => true, 'is_visible_in_catalog' => true, 'is_visible_in_menu' => true,
        ]);
    }
}
