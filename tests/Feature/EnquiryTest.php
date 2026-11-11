<?php

namespace Tests\Feature;

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnquiryTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kwame Asante', 'email' => 'kwame@example.com', 'phone' => '', 'subject' => 'Opening hours',
            'message' => 'Are you open on Saturdays?', 'privacy_consent' => '1',
        ], $this->spamFields(), $overrides);
    }

    public function test_enquiry_is_saved_as_new(): void
    {
        $this->post('/contact', $this->payload())->assertRedirect('/contact')->assertSessionHas('enquiry_sent');
        $e = Enquiry::sole();
        $this->assertSame(EnquiryStatus::New, $e->status);
        $this->assertNull($e->phone);
        $this->followingRedirects()->post('/contact', $this->payload())->assertSee('Message sent');
    }

    public function test_status_cannot_be_set_by_public(): void
    {
        $this->post('/contact', $this->payload(['status' => 'resolved', 'assigned_to' => 1]));
        $this->assertSame(EnquiryStatus::New, Enquiry::sole()->status);
        $this->assertNull(Enquiry::sole()->assigned_to);
    }

    public function test_validation_and_spam_protection(): void
    {
        $this->post('/contact', $this->spamFields())->assertSessionHasErrors(['name', 'email', 'subject', 'message', 'privacy_consent']);
        $this->post('/contact', $this->payload(['email' => 'nope', 'message' => 'short']))->assertSessionHasErrors(['email', 'message']);
        $this->post('/contact', $this->payload(['website_url' => 'spam']))->assertSessionHasErrors('form');
        $this->assertDatabaseCount('enquiries', 0);
    }

    public function test_contact_form_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $this->payload());
        }
        $this->post('/contact', $this->payload())->assertStatus(429);
    }

    public function test_product_enquiry_links_product(): void
    {
        $product = Product::factory()->create();
        $this->get('/contact?product='.$product->slug)->assertSee($product->name);
        $this->post('/contact', $this->payload(['product' => $product->slug]));
        $this->assertSame($product->id, Enquiry::sole()->product_id);
    }

    public function test_staff_can_update_assign_and_add_private_notes(): void
    {
        $staff = $this->staff('receptionist');
        $customer = User::factory()->create();
        $e = Enquiry::factory()->make();
        $e->user_id = $customer->id;
        $e->save();

        $this->actingAs($staff)->patch("/admin/enquiries/{$e->id}", ['status' => 'in_progress', 'assigned_to' => $staff->id])->assertSessionHasNoErrors();
        $this->actingAs($staff)->post("/admin/enquiries/{$e->id}/notes", ['note' => 'SECRET-INTERNAL-NOTE'])->assertSessionHasNoErrors();
        $e->refresh();
        $this->assertSame(EnquiryStatus::InProgress, $e->status);
        $this->assertSame($staff->id, $e->assigned_to);
        $this->actingAs($staff)->get("/admin/enquiries/{$e->id}")->assertSee('SECRET-INTERNAL-NOTE');

        // Cannot assign to someone who can't manage enquiries.
        $content = $this->staff('content-manager');
        $this->actingAs($staff)->patch("/admin/enquiries/{$e->id}", ['status' => 'in_progress', 'assigned_to' => $content->id])->assertSessionHasErrors('assigned_to');

        // Customer never sees the note.
        $this->actingAs($customer)->get('/account/enquiries')->assertSee($e->subject)->assertDontSee('SECRET-INTERNAL-NOTE');
    }

    public function test_content_manager_cannot_see_enquiries(): void
    {
        $e = Enquiry::factory()->create();
        $this->actingAs($this->staff('content-manager'))->get("/admin/enquiries/{$e->id}")->assertForbidden();
    }
}
