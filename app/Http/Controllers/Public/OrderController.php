<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;

/** Order status page and "pay again" for one order, reached through a signed link. */
class OrderController extends Controller
{
    public function show(Order $order)
    {
        return view('public.shop.order', ['order' => $order->load('items', 'pickupBranch')]);
    }

    public function pay(Order $order, PaymentService $payments)
    {
        if (! $order->isAwaitingPayment()) {
            return redirect()->to($order->publicUrl());
        }

        try {
            return redirect()->away($payments->start($order));
        } catch (PaymentGatewayException $e) {
            return redirect()->to($order->publicUrl())->with('error', $e->getMessage());
        }
    }
}
