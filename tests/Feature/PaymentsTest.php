<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Notifications\OrderPaid;
use App\Services\CartService;
use App\Services\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'sk_test_fake_secret_for_tests';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.secret_key' => self::SECRET, 'services.paystack.base_url' => 'https://api.paystack.test']);
        Notification::fake();
    }

    private function fakePaystack(string $status = 'success', ?int $amount = null, string $currency = 'GHS'): void
    {
        Http::fake([
            'api.paystack.test/transaction/initialize' => fn (HttpRequest $r) => Http::response(['status' => true, 'message' => 'ok', 'data' => [
                'authorization_url' => 'https://checkout.paystack.test/'.$r['reference'], 'access_code' => 'ac', 'reference' => $r['reference'],
            ]]),
            'api.paystack.test/transaction/verify/*' => function (HttpRequest $r) use ($status, $amount, $currency) {
                $reference = basename(parse_url($r->url(), PHP_URL_PATH));
                $payment = Payment::where('reference', $reference)->first();

                return Http::response(['status' => true, 'data' => [
                    'id' => 12345, 'status' => $status, 'reference' => $reference, 'currency' => $currency,
                    'amount' => $amount ?? $payment?->amount_minor, 'channel' => 'mobile_money', 'gateway_response' => 'Approved',
                ]]);
            },
        ]);
    }

    private function product(array $attrs = []): Product
    {
        $cat = ProductCategory::factory()->create(['is_active' => true]);

        return Product::factory()->create(array_merge([
            'product_category_id' => $cat->id, 'name' => 'Aviator Frame', 'slug' => 'aviator-frame',
            'price' => 250, 'is_published' => true, 'is_purchasable' => true, 'stock_quantity' => 3, 'availability_status' => 'available',
        ], $attrs));
    }

    private function checkout(array $overrides = []): TestResponse
    {
        $branch = Branch::factory()->create(['is_active' => true]);

        return $this->post('/checkout', array_merge([
            'customer_name' => 'Ama Mensah', 'customer_email' => 'ama@example.com', 'customer_phone' => '+233 24 123 4567',
            'fulfilment' => 'pickup', 'pickup_branch_id' => $branch->id, 'accept_terms' => '1',
        ], $this->spamFields(), $overrides));
    }

    public function test_shop_is_hidden_until_paystack_is_configured(): void
    {
        config(['services.paystack.secret_key' => null]);
        $this->product();
        $this->get('/cart')->assertNotFound();
        $this->get('/products/aviator-frame')->assertOk()->assertDontSee('Add to cart');
    }

    public function test_full_purchase_from_cart_to_paid_order(): void
    {
        $this->fakePaystack();
        $product = $this->product();

        $this->get('/products/aviator-frame')->assertOk()->assertSee('Add to cart');
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect('/cart');
        $this->get('/cart')->assertOk()->assertSee('Aviator Frame')->assertSee('GH₵ 500.00');

        $response = $this->checkout();
        $order = Order::sole();
        $payment = Payment::sole();
        $response->assertRedirect('https://checkout.paystack.test/'.$payment->reference);

        $this->assertSame('500.00', (string) $order->total);
        $this->assertSame(50000, $payment->amount_minor);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(0, app(CartService::class)->count());
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/transaction/initialize')
            && $r['amount'] === 50000 && $r['currency'] === 'GHS' && $r->hasHeader('Authorization', 'Bearer '.self::SECRET));

        // Customer returns from Paystack.
        $this->get('/payments/callback?reference='.$payment->reference)->assertRedirect($order->publicUrl());
        $order->refresh();
        $this->assertSame(OrderStatus::Paid, $order->status);
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame('mobile_money', $payment->fresh()->channel);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        Notification::assertSentOnDemand(OrderPaid::class);

        $this->get($order->publicUrl())->assertOk()->assertSee('Payment received')->assertSee($order->reference);

        // Repeating the callback (or a webhook) does not take stock twice.
        $this->get('/payments/callback?reference='.$payment->reference);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_prices_come_from_the_database_not_the_browser(): void
    {
        $this->fakePaystack();
        $product = $this->product(['price' => 300]);
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 1, 'price' => 1]);
        $this->checkout(['total' => 1, 'subtotal' => 1]);
        $this->assertSame('300.00', (string) Order::sole()->total);
        $this->assertSame(30000, Payment::sole()->amount_minor);
    }

    public function test_tampered_amount_is_not_accepted_as_paid(): void
    {
        $this->fakePaystack('success', amount: 100);
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $this->checkout();
        $payment = Payment::sole();

        $this->get('/payments/callback?reference='.$payment->reference);
        $this->assertSame(Payment::FAILED, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, Order::sole()->status);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_failed_or_abandoned_payment_leaves_order_unpaid(): void
    {
        $this->fakePaystack('abandoned');
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $this->checkout();
        $payment = Payment::sole();

        $this->get('/payments/callback?reference='.$payment->reference)->assertSessionHas('error');
        $this->assertSame(Payment::ABANDONED, $payment->fresh()->status);
        $order = Order::sole();
        $this->assertTrue($order->isAwaitingPayment());
        $this->get($order->publicUrl())->assertSee('Pay with Paystack');
    }

    public function test_cannot_buy_more_than_in_stock_or_unpublished_products(): void
    {
        $this->fakePaystack();
        $product = $this->product(['stock_quantity' => 1]);
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 5]);
        $this->assertSame([$product->id => 1], app(CartService::class)->raw());

        $hidden = $this->product(['slug' => 'hidden', 'is_published' => false]);
        $this->post('/cart', ['product_id' => $hidden->id])->assertSessionHas('error');

        $notForSale = $this->product(['slug' => 'enquire-only', 'is_purchasable' => false]);
        $this->post('/cart', ['product_id' => $notForSale->id])->assertSessionHas('error');
    }

    public function test_checkout_validates_details(): void
    {
        $this->fakePaystack();
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $this->checkout(['customer_email' => 'not-an-email', 'pickup_branch_id' => null, 'accept_terms' => null])
            ->assertSessionHasErrors(['customer_email', 'pickup_branch_id', 'accept_terms']);
        $this->checkout(['fulfilment' => 'delivery'])->assertSessionHasErrors('fulfilment'); // delivery is off by default
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_delivery_fee_is_added_when_delivery_is_enabled(): void
    {
        $this->fakePaystack();
        app(Settings::class)->put(['shop_delivery_enabled' => true, 'shop_delivery_fee' => 25]);
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $this->checkout(['fulfilment' => 'delivery', 'delivery_address' => '1 Ring Road, Accra'])->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('275.00', (string) $order->total);
        $this->assertNull($order->pickup_branch_id);
    }

    public function test_order_page_requires_a_valid_signed_link(): void
    {
        $this->fakePaystack();
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $this->checkout();
        $order = Order::sole();

        $this->get('/orders/'.$order->reference)->assertForbidden();
        $this->get($order->publicUrl())->assertOk();
    }

    public function test_webhook_requires_valid_signature_and_confirms_payment(): void
    {
        $this->fakePaystack();
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $this->checkout();
        $payment = Payment::sole();
        $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);

        $this->call('POST', '/paystack/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'wrong'], $body)->assertStatus(401);
        $this->assertSame(Payment::PENDING, $payment->fresh()->status);

        $this->call('POST', '/paystack/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => hash_hmac('sha512', $body, self::SECRET)], $body)->assertOk();
        $this->assertSame(Payment::SUCCESS, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Paid, Order::sole()->status);
    }

    public function test_appointment_deposit_is_30_percent_of_consultation_fee_and_paid_at_booking(): void
    {
        $this->fakePaystack();
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();

        $this->get('/appointments/book')->assertSee('GH₵ 500.00')->assertSee('GH₵ 150.00')->assertSee('Continue to pay deposit');

        // Submitting the request goes straight to Paystack for the deposit.
        $response = $this->post('/appointments', $this->bookingPayload($branch, $service));
        $appointment = Appointment::sole();
        $payment = Payment::sole();
        $response->assertRedirect('https://checkout.paystack.test/'.$payment->reference);
        $this->assertSame(15000, $payment->amount_minor);
        $this->assertSame('500.00', (string) $appointment->consultation_fee);
        $this->assertSame(30, $appointment->deposit_percent);
        $this->assertSame(PaymentStatus::Unpaid, $appointment->payment_status);

        // Staff cannot confirm before the deposit is paid.
        $staff = $this->staff('receptionist');
        $confirm = ['status' => 'confirmed', 'start_date' => $appointment->requested_date->toDateString(), 'start_time' => substr($appointment->requested_time, 0, 5), 'notify' => '1'];
        $this->actingAs($staff)->get("/admin/appointments/{$appointment->reference_code}")->assertOk()->assertSee('Record deposit paid at branch')->assertSee('GH₵ 150.00');
        $this->actingAs($staff)->patch("/admin/appointments/{$appointment->reference_code}", $confirm)->assertSessionHasErrors('status');

        auth()->logout();
        $this->get('/appointments/'.$appointment->reference_code.'/pay')->assertForbidden();
        $this->get('/payments/callback?reference='.$payment->reference)->assertRedirect($appointment->paymentUrl());
        $appointment->refresh();
        $this->assertSame(PaymentStatus::Paid, $appointment->payment_status);
        $this->assertSame('pending', $appointment->status->value); // paying never confirms
        $this->assertSame(350.0, $appointment->balanceDue());
        $this->get($appointment->paymentUrl())->assertSee('Deposit paid')->assertSee('GH₵ 350.00')->assertSee('not yet confirmed');

        $this->actingAs($staff)->get("/admin/appointments/{$appointment->reference_code}")->assertOk()->assertSee('GH₵ 350.00');
        $this->actingAs($staff)->patch("/admin/appointments/{$appointment->reference_code}", $confirm)->assertSessionHasNoErrors();
        $this->assertSame('confirmed', $appointment->fresh()->status->value);
        $this->actingAs($staff)->get("/admin/appointments/{$appointment->reference_code}")->assertOk();
    }

    public function test_service_fee_overrides_standard_fee_and_deposit_can_be_recorded_at_branch(): void
    {
        $this->fakePaystack();
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $service->update(['booking_fee' => 200]);
        $this->post('/appointments', $this->bookingPayload($branch, $service));
        $appointment = Appointment::sole();
        $this->assertSame('60.00', (string) $appointment->amount_due);

        $this->actingAs($this->staff('receptionist'))->post("/admin/appointments/{$appointment->reference_code}/deposit")->assertRedirect();
        $this->assertSame(PaymentStatus::Paid, $appointment->fresh()->payment_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.deposit_recorded']);
    }

    public function test_no_fee_when_payments_not_configured(): void
    {
        config(['services.paystack.secret_key' => null]);
        $this->freezeMonday();
        [$branch, $service] = $this->branchWithService();
        $this->post('/appointments', $this->bookingPayload($branch, $service))->assertRedirect('/appointments/received');
        $this->assertSame(PaymentStatus::NotRequired, Appointment::sole()->payment_status);
    }

    public function test_staff_manage_orders_and_roles_are_enforced(): void
    {
        $this->fakePaystack();
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);
        $this->checkout();
        $this->get('/payments/callback?reference='.Payment::sole()->reference);
        $order = Order::sole();
        $this->assertSame(1, $product->fresh()->stock_quantity);

        $reception = $this->staff('receptionist');
        $this->actingAs($reception)->get('/admin/orders')->assertOk()->assertSee($order->reference);
        $this->actingAs($reception)->patch("/admin/orders/{$order->reference}", ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->actingAs($reception)->patch("/admin/orders/{$order->reference}", ['status' => 'ready_for_pickup'])->assertSessionHasNoErrors();
        $this->assertSame(OrderStatus::ReadyForPickup, $order->fresh()->status);
        $this->actingAs($reception)->get('/admin/payments')->assertForbidden();

        $admin = $this->staff('administrator');
        $this->actingAs($admin)->get('/admin/payments')->assertOk()->assertSee('GH₵ 500.00');
        $this->actingAs($admin)->patch("/admin/orders/{$order->reference}", ['status' => 'cancelled'])->assertSessionHasNoErrors();
        $this->assertSame(3, $product->fresh()->stock_quantity); // returned to stock
        $this->actingAs($admin)->post("/admin/orders/{$order->reference}/refund")->assertRedirect();
        $this->assertSame(PaymentStatus::Refunded, $order->fresh()->payment_status);

        $this->actingAs($this->staff('content-manager'))->get('/admin/orders')->assertForbidden();
    }

    public function test_paystack_errors_keep_the_order_and_offer_retry(): void
    {
        Http::fake(['api.paystack.test/*' => Http::response(['status' => false, 'message' => 'Invalid key'], 401)]);
        $product = $this->product();
        $this->post('/cart', ['product_id' => $product->id]);
        $order = null;
        $this->checkout()->assertRedirect();
        $order = Order::sole();
        $this->assertTrue($order->isAwaitingPayment());
        $this->assertSame(Payment::FAILED, Payment::sole()->status);
    }

    public function test_customer_sees_own_orders(): void
    {
        $this->fakePaystack();
        $customer = User::factory()->create();
        $product = $this->product();
        $this->actingAs($customer)->post('/cart', ['product_id' => $product->id]);
        $this->actingAs($customer)->checkout();
        $this->actingAs($customer)->get('/account/orders')->assertOk()->assertSee(Order::sole()->reference);
        $this->actingAs(User::factory()->create())->get('/account/orders')->assertDontSee(Order::sole()->reference);
    }
}
