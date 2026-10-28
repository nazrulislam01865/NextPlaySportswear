<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\PersonalizationTemplate;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PersonalizationCategoryIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    public function test_categories_are_listed_once_with_template_details_instead_of_products(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $jerseys = $this->category('Basketball Jerseys');
        $this->template($jerseys, 'Player Text', 'Player Name');
        $this->template($jerseys, 'Team Number', 'Player Number');
        $this->category('Unconfigured Caps');

        $this->get(route('admin.personalization-categories.index'))
            ->assertOk()
            ->assertSee('Basketball Jerseys')
            ->assertSee('Personalization Added')
            ->assertSee('Player Name')
            ->assertSee('Player Number')
            ->assertSee('Player Text')
            ->assertSee('Team Number')
            ->assertDontSee('Unconfigured Caps')
            ->assertDontSee('With Personalization')
            ->assertViewHas('categories', fn ($paginator) => $paginator->total() === 1);
    }

    public function test_configured_category_is_excluded_from_new_setup_but_tagged_while_editing(): void
    {
        $this->actingAs($this->admin(), 'admin');
        $jerseys = $this->category('Basketball Jerseys');
        $caps = $this->category('Custom Caps');
        $template = $this->template($jerseys, 'Player Text', 'Player Name');

        $this->get(route('admin.personalization-templates.create'))
            ->assertOk()->assertDontSee('Basketball Jerseys')->assertSee('Custom Caps');
        $this->get(route('admin.personalization-templates.edit', $template))
            ->assertOk()->assertSee('Basketball Jerseys')->assertSee('Personalization Added');

        $data = [
            'category_id' => $jerseys->id, 'name' => 'Second Setup',
            'section_title' => 'Other Name', 'shared_entry_enabled' => '1', 'is_active' => '1',
        ];
        $this->post(route('admin.personalization-templates.store'), $data)
            ->assertSessionHasErrors('category_id');
        $this->assertSame(1, PersonalizationTemplate::query()->count());

        $this->post(route('admin.personalization-templates.store'), array_replace($data, ['category_id' => $caps->id]))
            ->assertRedirect();
        $this->assertSame(2, PersonalizationTemplate::query()->count());
    }

    public function test_category_search_and_access_controls(): void
    {
        $one = $this->category('Basketball Jerseys');
        $two = $this->category('Training Caps');
        $this->template($one, 'Player Text', 'Player Name');
        $this->template($two, 'Custom Logo', 'Logo Upload');

        $this->get(route('admin.personalization-categories.index'))->assertRedirect();
        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.personalization-categories.index', ['q' => 'Logo Upload']))
            ->assertOk()->assertSee('Training Caps')->assertDontSee('Basketball Jerseys');
        $this->get(route('admin.personalization-categories.index', ['q' => str_repeat('a', 121)]))
            ->assertSessionHasErrors('q');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    private function category(string $name): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'display_type' => 'collection', 'category_type' => 'standard',
            'page_template' => 'product_grid', 'status' => 'active',
            'is_active' => true, 'is_visible_in_catalog' => true, 'is_visible_in_menu' => true,
        ]);
    }

    private function template(Category $category, string $name, string $label): PersonalizationTemplate
    {
        return PersonalizationTemplate::query()->create([
            'category_id' => $category->id,
            'name' => $name,
            'section_title' => $name,
            'fields' => [['key' => Str::slug($label, '_'), 'label' => $label, 'type' => 'text', 'enabled' => true]],
            'is_active' => true, 'is_published' => true,
        ]);
    }
}
