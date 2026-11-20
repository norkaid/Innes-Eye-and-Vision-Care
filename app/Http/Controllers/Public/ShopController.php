<?php

namespace App\Http\Controllers\Public;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CheckoutRequest;
use App\Models\Branch;
use App\Models\Order;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Services\CartService;
use App\Services\PaymentService;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function __construct(private CartService $cart, private ShopService $shop) {}

    public function cart()
    {
        $lines = $this->cart->lines();

        return view('public.shop.cart', ['lines' => $lines, 'subtotal' => $this->cart->subtotal($lines)]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);
        $product = Product::published()->find($data['product_id']);
        if (! $product || ! $product->canBeBought()) {
            return back()->with('error', 'Sorry, this product cannot be bought online at the moment.');
        }
        $quantity = $this->cart->add($product, (int) ($data['quantity'] ?? 1));

        return redirect()->route('shop.cart')->with('success', "Added {$product->name} to your cart (quantity {$quantity}).");
    }

    public function update(Request $request)
    {
        $data = $request->validate(['quantities' => ['array'], 'quantities.*' => ['integer', 'min:0', 'max:10']]);
        foreach ($data['quantities'] ?? [] as $id => $quantity) {
            $product = Product::find((int) $id);
            if ($product) {
                $this->cart->set($product, (int) $quantity);
            }
        }

        return redirect()->route('shop.cart')->with('success', 'Cart updated.');
    }

    public function remove(int $product)
    {
        $this->cart->remove($product);

        return redirect()->route('shop.cart')->with('success', 'Item removed.');
    }

    public function checkout(Request $request)
    {
        $lines = $this->cart->lines();
        if ($lines->isEmpty()) {
            return redirect()->route('shop.cart');
        }
        $user = $request->user();

        return view('public.shop.checkout', [
            'lines' => $lines,
            'subtotal' => $this->cart->subtotal($lines),
            'options' => $this->shop->fulfilmentOptions(),
            'deliveryFee' => $this->shop->deliveryFee(),
            'branches' => Branch::active()->orderBy('display_order')->orderBy('name')->get(),
            'prefill' => $user && $user->isCustomer() ? ['customer_name' => $user->name, 'customer_email' => $user->email, 'customer_phone' => $user->phone] : [],
        ]);
    }

    public function placeOrder(CheckoutRequest $request, PaymentService $payments, AuditLogger $audit)
    {
        $data = $request->validated();
        $order = DB::transaction(function () use ($data, $request) {
            $lines = $this->cart->lines();
            if ($lines->isEmpty()) {
                throw ValidationException::withMessages(['form' => 'Your cart is empty or the items are no longer available.']);
            }

            // Re-check stock with the rows locked, and use database prices only.
            $subtotal = 0;
            $items = [];
            foreach ($lines as $line) {
                $product = Product::whereKey($line['product']->id)->lockForUpdate()->first();
                if (! $product || ! $product->canBeBought() || ($product->stock_quantity !== null && $product->stock_quantity < $line['quantity'])) {
                    throw ValidationException::withMessages(['form' => "Sorry, {$line['product']->name} is no longer available in that quantity. Please update your cart."]);
                }
                $lineTotal = round((float) $product->price * $line['quantity'], 2);
                $subtotal += $lineTotal;
                $items[] = ['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->price, 'quantity' => $line['quantity'], 'line_total' => $lineTotal];
            }
            $deliveryFee = $data['fulfilment'] === 'delivery' ? $this->shop->deliveryFee() : 0;

            $order = new Order([
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'fulfilment' => $data['fulfilment'],
                'pickup_branch_id' => $data['fulfilment'] === 'pickup' ? $data['pickup_branch_id'] : null,
                'delivery_address' => $data['fulfilment'] === 'delivery' ? $data['delivery_address'] : null,
                'customer_note' => $data['customer_note'] ?? null,
            ]);
            $order->reference = Order::newReference();
            $order->subtotal = round($subtotal, 2);
            $order->delivery_fee = $deliveryFee;
            $order->total = round($subtotal + $deliveryFee, 2);
            $order->currency = config('services.paystack.currency', 'GHS');
            $order->status = OrderStatus::PendingPayment;
            $order->payment_status = PaymentStatus::Unpaid;
            if ($request->user()?->isCustomer()) {
                $order->user_id = $request->user()->id;
            }
            $order->save();
            $order->items()->createMany($items);

            return $order;
        });

        $this->cart->clear();
        $audit->log('order.placed', $order, ['reference' => $order->reference, 'total' => (string) $order->total]);

        try {
            return redirect()->away($payments->start($order));
        } catch (PaymentGatewayException $e) {
            return redirect()->to($order->publicUrl())->with('error', 'Your order was saved, but we could not open the payment page: '.$e->getMessage().' Please try again below.');
        }
    }
}
