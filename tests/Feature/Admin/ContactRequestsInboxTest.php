<?php

namespace Tests\Feature\Admin;

use App\Models\ContactMessage;
use App\Models\User;
use App\Support\AdminRbac;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactRequestsInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        AdminRbac::syncDefaults(true);
    }

    private function admin(string $role = 'super_admin'): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function contact(array $attributes = []): ContactMessage
    {
        return ContactMessage::query()->create(array_merge([
            'name' => 'Avery Customer',
            'email' => 'avery@example.com',
            'phone' => '+44 7700 900123',
            'topic' => 'order-support',
            'order_number' => 'NP-2026-001',
            'message' => "Original customer enquiry\nWith a second line.",
            'status' => ContactMessage::STATUS_NEW,
        ], $attributes));
    }

    public function test_guest_cannot_view_list_or_details_or_attachments(): void
    {
        $contact = $this->contact();
        $this->get(route('admin.contact-requests.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.contact-requests.show', $contact))->assertRedirect(route('admin.login'));
        $this->get(route('admin.contact-requests.attachment', $contact))->assertRedirect(route('admin.login'));
    }

    public function test_support_agent_sees_submission_and_admin_navigation(): void
    {
        $contact = $this->contact();
        $this->actingAs($this->admin('support_agent'), 'admin');

        $this->get(route('admin.contact-requests.index'))->assertOk()
            ->assertSee('Contact Requests')->assertSee('Avery Customer')
            ->assertSee('NP-2026-001');
        $this->get(route('admin.contact-requests.show', $contact))->assertOk()
            ->assertSee('Original customer enquiry')->assertSee('With a second line.')
            ->assertSee('Reply by email')->assertSee('Request status');
    }

    public function test_content_editor_cannot_read_private_contact_submissions(): void
    {
        $contact = $this->contact();
        $this->actingAs($this->admin('content_manager'), 'admin');
        $this->get(route('admin.contact-requests.index'))->assertForbidden();
        $this->get(route('admin.contact-requests.show', $contact))->assertForbidden();
        $this->patch(route('admin.contact-requests.update', $contact), ['status' => 'closed'])->assertForbidden();
        $this->get(route('admin.contact-requests.attachment', $contact))->assertForbidden();
        $this->get(route('admin.contact-page.attachment', $contact))->assertForbidden();
    }

    public function test_view_only_role_can_read_but_cannot_change_status(): void
    {
        $admin = $this->admin('order_manager');
        $roleId = DB::table('admin_roles')->where('slug', 'order_manager')->value('id');
        $permissionId = DB::table('admin_permissions')->where('key', 'contact_requests.manage')->value('id');
        DB::table('admin_role_permissions')
            ->where('role_id', $roleId)->where('permission_id', $permissionId)
            ->update(['allowed' => false]);
        $contact = $this->contact();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.contact-requests.show', $contact))
            ->assertOk()->assertSee('Read-only access');
        $this->patch(route('admin.contact-requests.update', $contact), [
            'status' => ContactMessage::STATUS_CLOSED,
        ])->assertForbidden();
        $this->assertSame(ContactMessage::STATUS_NEW, $contact->fresh()->status);
    }

    public function test_status_and_topic_filters_do_not_show_other_enquiries(): void
    {
        $this->contact();
        $this->contact(['name' => 'Taylor Example', 'email' => 'taylor@example.com', 'topic' => 'shipping', 'status' => 'resolved']);
        $this->actingAs($this->admin(), 'admin');

        $this->get(route('admin.contact-requests.index', ['status' => 'new', 'topic' => 'order-support']))
            ->assertOk()->assertSee('Avery Customer')->assertDontSee('Taylor Example');
        $this->get(route('admin.contact-requests.index', ['q' => 'taylor@example.com']))
            ->assertOk()->assertSee('Taylor Example')->assertDontSee('Avery Customer');
    }

    public function test_status_and_internal_notes_are_saved_without_exposing_notes_as_plaintext(): void
    {
        $contact = $this->contact();
        $this->actingAs($this->admin(), 'admin')
            ->patch(route('admin.contact-requests.update', $contact), [
                'status' => 'in_review',
                'internal_note' => 'Waiting on warehouse confirmation.',
            ])->assertRedirect(route('admin.contact-requests.show', $contact));

        $contact->refresh();
        $this->assertSame('in_review', $contact->status);
        $this->assertSame('Waiting on warehouse confirmation.', $contact->internal_note);
        $this->assertStringNotContainsString('Waiting on warehouse confirmation.',
            (string) DB::table('contact_messages')->where('id', $contact->id)->value('internal_note'));
        $this->get(route('admin.contact-requests.show', $contact))->assertSee('Waiting on warehouse confirmation.');
    }

    public function test_repeated_identical_update_does_not_touch_timestamp(): void
    {
        $contact = $this->contact(['status' => ContactMessage::STATUS_RESOLVED, 'internal_note' => 'Resolved via email.']);
        $originalTimestamp = $contact->updated_at->toDateTimeString();
        $this->actingAs($this->admin(), 'admin')->patch(route('admin.contact-requests.update', $contact), [
            'status' => 'resolved', 'internal_note' => 'Resolved via email.',
        ])->assertRedirect();
        $this->assertSame($originalTimestamp, $contact->fresh()->updated_at->toDateTimeString());
    }

    public function test_private_file_download_and_missing_file_handling(): void
    {
        Storage::fake('local');
        $contact = $this->contact(['attachment_name' => 'artwork.png', 'attachment_mime' => 'image/png']);
        $path = 'contact-messages/'.$contact->id.'/artwork.png';
        Storage::disk('local')->put($path, 'private file content');
        $contact->update(['attachment_path' => $path]);
        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.contact-requests.attachment', $contact))
            ->assertOk()->assertDownload('artwork.png');
        $contact->update(['attachment_path' => 'contact-messages/999/someone-elses-file.png']);
        $this->get(route('admin.contact-requests.attachment', $contact))->assertNotFound();
    }

    public function test_new_storefront_submission_is_visible_in_contact_inbox(): void
    {
        config()->set('transactional_email.enabled', false);
        config()->set('services.recaptcha.site_key', 'test-site-key');
        config()->set('services.recaptcha.secret_key', 'test-secret-key');
        \Illuminate\Support\Facades\Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'hostname' => parse_url(route('contact.store'), PHP_URL_HOST),
            ], 200),
        ]);
        $this->post(route('contact.store'), [
            'name' => 'Incoming Contact',
            'email' => 'incoming@example.com',
            'topic' => 'product-question',
            'message' => 'Could you provide product dimensions, please?',
            'company' => '',
            'g-recaptcha-response' => 'valid-test-token',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $contact = ContactMessage::query()->where('email', 'incoming@example.com')->firstOrFail();
        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.contact-requests.index'))
            ->assertOk()->assertSee('Incoming Contact');
        $this->get(route('admin.contact-requests.show', $contact))
            ->assertOk()->assertSee('Could you provide product dimensions, please?');
    }

    public function test_paginated_inbox_limits_rows(): void
    {
        foreach (range(1, 30) as $number) {
            $this->contact(['name' => 'Customer '.$number, 'email' => 'customer'.$number.'@example.com']);
        }
        $this->actingAs($this->admin(), 'admin');
        $this->get(route('admin.contact-requests.index'))
            ->assertOk()->assertSee('Customer 30')->assertDontSee('customer1@example.com');
    }
}
