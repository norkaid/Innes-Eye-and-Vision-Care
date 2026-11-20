<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request, PaystackClient $paystack)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in([Payment::PENDING, Payment::SUCCESS, Payment::FAILED, Payment::ABANDONED])],
            'type' => ['nullable', Rule::in(['order', 'appointment'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = Payment::with('payable')
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->where('reference', 'like', "%{$t}%")->orWhere('customer_email', 'like', "%{$t}%")))
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when($filters['type'] ?? null, fn ($q, $t) => $q->where('payable_type', $t === 'order' ? (new Order)->getMorphClass() : (new Appointment)->getMorphClass()))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d));

        $received = (clone $query)->where('status', Payment::SUCCESS)->sum('amount_minor') / 100;

        return view('admin.payments.index', [
            'payments' => $query->latest()->paginate(30)->withQueryString(),
            'filters' => $filters,
            'received' => $received,
            'configured' => $paystack->configured(),
            'testMode' => $paystack->isTestMode(),
        ]);
    }

    /** Asks Paystack again for the outcome of a payment that is still "started". */
    public function recheck(Payment $payment, PaymentService $payments)
    {
        try {
            $payment = $payments->confirm($payment->reference);
        } catch (PaymentGatewayException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Payment {$payment->reference}: {$payment->statusLabel()}.");
    }
}
