<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Notifications\AppointmentStatusChanged;
use App\Services\AvailabilityService;
use App\Services\Settings;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsAndSlotsTest extends TestCase
{
    use RefreshDatabase;

    private function confirmPayload(Appointment $a): array
    {
        return ['status' => 'confirmed', 'start_date' => $a->requested_date->toDateString(), 'start_time' => substr($a->requested_time, 0, 5), 'notify' => '1'];
    }

    public function test_confirmation_is_sent_by_sms_and_email_as_chosen(): void
    {
        Notification::fake();
        Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success', 'data' => []])]);
        config(['services.sms.driver' => 'arkesel', 'services.sms.arkesel_key' => 'test-key', 'services.sms.sender_id' => 'InnesEye']);
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        $this->post('/appointments', $this->bookingPayload($branch, $service, ['customer_phone' => '024 123 4567', 'notify_email' => '1', 'notify_sms' => '1']));
        $a = Appointment::sole();
        $this->assertTrue($a->notify_sms);

        $this->actingAs($this->staff('receptionist'))->patch("/admin/appointments/{$a->reference_code}", $this->confirmPayload($a))->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(AppointmentStatusChanged::class);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'sms.arkesel.com')
            && $r['recipients'] === ['233241234567']
            && str_contains($r['message'], 'CONFIRMED')
            && str_contains($r['message'], $a->reference_code)
            && $r->hasHeader('api-key', 'test-key'));
        $this->assertDatabaseHas('notification_deliveries', ['channel' => 'sms', 'status' => 'sent', 'notification_type' => 'AppointmentStatusSms']);
    }

    public function test_sms_only_customer_gets_no_email(): void
    {
        Notification::fake();
        Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success'])]);
        config(['services.sms.driver' => 'arkesel', 'services.sms.arkesel_key' => 'k']);
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['notify_email' => '0', 'notify_sms' => '1']));
        $a = Appointment::sole();

        $this->actingAs($this->staff('receptionist'))->patch("/admin/appointments/{$a->reference_code}", $this->confirmPayload($a));
        Notification::assertNothingSent();
        Http::assertSentCount(2); // request received + confirmed
    }

    public function test_failed_sms_is_recorded_and_does_not_break_confirmation(): void
    {
        Notification::fake();
        Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'error', 'message' => 'Insufficient balance'], 400)]);
        config(['services.sms.driver' => 'arkesel', 'services.sms.arkesel_key' => 'k']);
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $this->post('/appointments', $this->bookingPayload($branch, $service));
        $a = Appointment::sole();

        $this->actingAs($this->staff('receptionist'))->patch("/admin/appointments/{$a->reference_code}", $this->confirmPayload($a))->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $a->fresh()->status->value);
        $this->assertDatabaseHas('notification_deliveries', ['channel' => 'sms', 'status' => 'failed']);
    }

    public function test_phone_numbers_are_normalised_for_ghana(): void
    {
        $this->assertSame('233241234567', SmsService::normalise('024 123 4567'));
        $this->assertSame('233241234567', SmsService::normalise('+233 24 123 4567'));
        $this->assertSame('233241234567', SmsService::normalise('00233-24-123-4567'));
        $this->assertNull(SmsService::normalise('123'));
    }

    public function test_confirmed_time_is_never_offered_again_even_with_capacity(): void
    {
        Notification::fake();
        $now = $this->freezeMonday();
        app(Settings::class)->put(['slot_capacity' => 3]);
        [$branch, $service] = $this->branchWithService();
        $this->post('/appointments', $this->bookingPayload($branch, $service));
        $a = Appointment::sole();
        $date = $a->requested_date->copy();
        $time = substr($a->requested_time, 0, 5);
        $slots = fn () => app(AvailabilityService::class)->availableSlots($branch->fresh('openingHours'), Carbon::parse($date));

        $this->assertContains($time, $slots());
        $this->actingAs($this->staff('receptionist'))->patch("/admin/appointments/{$a->reference_code}", $this->confirmPayload($a))->assertSessionHasNoErrors();
        $this->assertNotContains($time, $slots());

        auth()->logout();
        $this->assertNotContains($time, $this->getJson('/appointments/availability?branch='.$branch->id.'&date='.$date->toDateString())->json('slots'));

        // A new request for that exact time is rejected.
        $this->travelTo($now->copy()->addMinutes(5));
        $this->post('/appointments', $this->bookingPayload($branch, $service, ['customer_email' => 'second@example.com']))->assertSessionHasErrors('requested_time');
        $this->assertSame(1, Appointment::count());
    }

    public function test_priced_products_are_bought_not_enquired(): void
    {
        config(['services.paystack.secret_key' => 'sk_test_x']);
        $cat = ProductCategory::factory()->create(['is_active' => true]);
        Product::factory()->create(['product_category_id' => $cat->id, 'slug' => 'priced', 'price' => 300, 'is_published' => true, 'availability_status' => 'available']);
        Product::factory()->create(['product_category_id' => $cat->id, 'slug' => 'no-price', 'price' => null, 'is_published' => true]);

        foreach ([false, true] as $delivery) {
            app(Settings::class)->put(['shop_delivery_enabled' => $delivery]);
            $this->get('/products/priced')->assertSee('Add to cart')->assertDontSee('Enquire About This Product')
                ->assertDontSee('@if')->assertDontSee('@endif')->assertDontSee('@unless');
        }
        $this->get('/products/no-price')->assertDontSee('Add to cart')->assertSee('Enquire About This Product')
            ->assertDontSee('@if')->assertDontSee('@endif');
    }

    public function test_settings_test_button_reports_honestly(): void
    {
        Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success'])]);
        config(['services.sms.driver' => 'arkesel', 'services.sms.arkesel_key' => 'k', 'mail.default' => 'array']);
        $this->actingAs($this->staff('super-admin'))->post('/admin/settings/test-notification', ['test_email' => 'me@example.com', 'test_phone' => '0241234567'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'Test email sent') && str_contains($m, 'Test SMS sent'));

        config(['services.sms.driver' => 'log', 'mail.default' => 'log']);
        $this->actingAs($this->staff('super-admin'))->post('/admin/settings/test-notification', ['test_email' => 'me@example.com', 'test_phone' => '0241234567'])
            ->assertSessionHas('success', fn ($m) => str_contains($m, 'laravel.log only'));

        $this->actingAs($this->staff('administrator'))->post('/admin/settings/test-notification', ['test_email' => 'me@example.com'])->assertForbidden();
    }

    public function test_admin_sees_why_a_product_is_not_on_sale(): void
    {
        $cat = ProductCategory::factory()->create(['is_active' => true]);
        $product = Product::factory()->create(['product_category_id' => $cat->id, 'price' => 344, 'is_published' => true]);
        $admin = $this->staff('content-manager');

        config(['services.paystack.secret_key' => null]);
        $this->actingAs($admin)->get("/admin/products/{$product->id}/edit")->assertSee('Not on sale online yet')->assertSee('PAYSTACK_SECRET_KEY');

        config(['services.paystack.secret_key' => 'sk_test_x']);
        $this->actingAs($admin)->get("/admin/products/{$product->id}/edit")->assertSee('Customers can buy this product online');
    }
}
