<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            'payment' => ['nullable', Rule::enum(PaymentStatus::class)],
        ]);

        $orders = Order::with(['items', 'pickupBranch'])
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['payment'] ?? null, fn ($q, $s) => $q->where('payment_status', $s))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.orders.index', compact('orders', 'filters'));
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', ['order' => $order->load(['items.product', 'pickupBranch', 'payments', 'user', 'handler'])]);
    }

    public function update(Request $request, Order $order)
    {
        $allowed = array_map(fn ($s) => $s->value, $order->status->staffTransitions($order->fulfilment));
        $data = $request->validate([
            'status' => ['required', Rule::in($allowed)],
            'staff_note' => ['nullable', 'string', 'max:3000'],
        ], ['status.in' => 'That change is not allowed for this order.']);

        $old = $order->status;
        DB::transaction(function () use ($order, $data, $request) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            $new = OrderStatus::from($data['status']);
            // Cancelling a paid order puts the items back into stock.
            if ($new === OrderStatus::Cancelled && $order->payment_status === PaymentStatus::Paid) {
                foreach ($order->items as $item) {
                    $product = $item->product_id ? Product::withTrashed()->lockForUpdate()->find($item->product_id) : null;
                    if ($product && $product->stock_quantity !== null) {
                        $product->increment('stock_quantity', $item->quantity);
                    }
                }
            }
            $order->status = $new;
            $order->handled_by = $request->user()->id;
            if (array_key_exists('staff_note', $data)) {
                $order->staff_note = $data['staff_note'];
            }
            $order->save();
        });
        $order->refresh();
        $this->audit->log('order.status_changed', $order, ['from' => $old->value, 'to' => $order->status->value]);

        $message = 'Order updated.';
        if ($order->status === OrderStatus::Cancelled && $order->payment_status === PaymentStatus::Paid) {
            $message .= ' This order was paid: refund the customer in the Paystack dashboard, then click "Record refund".';
        }

        return back()->with('success', $message);
    }

    public function note(Request $request, Order $order)
    {
        $data = $request->validate(['staff_note' => ['nullable', 'string', 'max:3000']]);
        $order->staff_note = $data['staff_note'];
        $order->save();

        return back()->with('success', 'Internal note saved.');
    }

    /** Records that the money was returned (the refund itself is made in the Paystack dashboard). */
    public function refund(Order $order)
    {
        abort_unless($order->payment_status === PaymentStatus::Paid, 422);
        $order->payment_status = PaymentStatus::Refunded;
        $order->save();
        $this->audit->log('order.refund_recorded', $order, ['reference' => $order->reference]);

        return back()->with('success', 'Refund recorded.');
    }
}
