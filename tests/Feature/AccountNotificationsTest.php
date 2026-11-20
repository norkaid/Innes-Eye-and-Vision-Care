<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Notifications\AccountMessage;
use App\Notifications\OrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sees_appointment_updates_in_their_account(): void
    {
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $customer = User::factory()->create(['first_name' => 'Ama']);

        $this->actingAs($customer)->post('/appointments', $this->bookingPayload($branch, $service));
        $a = Appointment::sole();
        $this->assertSame($customer->id, $a->user_id);

        $this->actingAs($this->staff('receptionist'))->patch("/admin/appointments/{$a->reference_code}", [
            'status' => 'confirmed', 'start_date' => $a->requested_date->toDateString(), 'start_time' => substr($a->requested_time, 0, 5), 'notify' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $customer->notifications()->count());
        $this->actingAs($customer)->get('/')->assertSee('new notifications');
        $this->actingAs($customer)->get('/account/notifications')->assertOk()
            ->assertSee('Request received')->assertSee('Confirmed')->assertSee('CONFIRMED for')->assertSee('New');
        $this->assertSame(0, $customer->unreadNotifications()->count());

        // Another customer never sees them.
        $this->actingAs(User::factory()->create())->get('/account/notifications')->assertDontSee($a->reference_code);
    }

    public function test_profile_preferences_are_saved_and_prefill_booking_form(): void
    {
        $this->branchWithService();
        $customer = User::factory()->create();
        $this->actingAs($customer)->patch('/account/profile', [
            'first_name' => $customer->first_name, 'last_name' => $customer->last_name, 'email' => $customer->email,
            'phone' => '024 123 4567', 'notify_email' => '1', 'notify_sms' => '0',
        ])->assertSessionHasNoErrors();
        $customer->refresh();
        $this->assertTrue($customer->notify_email);
        $this->assertFalse($customer->notify_sms);

        $html = $this->actingAs($customer)->get('/appointments/book')->getContent();
        preg_match('/<input[^>]*type="checkbox"[^>]*name="notify_sms"[^>]*>/', $html, $sms);
        preg_match('/<input[^>]*type="checkbox"[^>]*name="notify_email"[^>]*>/', $html, $email);
        $this->assertStringNotContainsString('checked', $sms[0]);
        $this->assertStringContainsString('checked', $email[0]);
    }

    public function test_order_ready_for_pickup_notifies_customer_everywhere(): void
    {
        Notification::fake();
        Http::fake(['sms.arkesel.com/*' => Http::response(['status' => 'success'])]);
        config(['services.sms.driver' => 'arkesel', 'services.sms.arkesel_key' => 'k']);
        $customer = User::factory()->create();
        $cat = ProductCategory::factory()->create();
        $product = Product::factory()->create(['product_category_id' => $cat->id]);
        $order = new Order(['customer_name' => 'Ama', 'customer_email' => 'ama@example.com', 'customer_phone' => '0241234567', 'fulfilment' => 'pickup']);
        $order->forceFill(['reference' => Order::newReference(), 'subtotal' => 100, 'total' => 100, 'status' => OrderStatus::Paid, 'payment_status' => 'paid', 'user_id' => $customer->id])->save();
        $order->items()->create(['product_id' => $product->id, 'product_name' => 'Frame', 'unit_price' => 100, 'quantity' => 1, 'line_total' => 100]);

        $this->actingAs($this->staff('receptionist'))->patch("/admin/orders/{$order->reference}", ['status' => 'ready_for_pickup', 'notify' => '1'])->assertSessionHasNoErrors();

        Notification::assertSentOnDemand(OrderStatusChanged::class);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r['message'], 'ready to collect'));
        Notification::assertSentTo($customer, AccountMessage::class, fn ($n) => str_contains($n->body, 'ready to collect'));
    }
}
