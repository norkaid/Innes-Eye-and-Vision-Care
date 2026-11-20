<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /** Paystack sends the customer back here after paying (or giving up). */
    public function callback(Request $request, PaymentService $payments)
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $payment = Payment::where('reference', $reference)->first();
        if (! $payment || ! $payment->payable) {
            return redirect()->route('home')->with('error', 'We could not find that payment. If money was taken, please contact us with the payment reference.');
        }

        try {
            $payment = $payments->confirm($reference);
        } catch (PaymentGatewayException $e) {
            return redirect()->to($payment->payable->paymentReturnUrl())
                ->with('error', 'We could not check your payment just now. If it went through, this page will update shortly – please refresh in a minute.');
        }

        $back = redirect()->to($payment->payable->paymentReturnUrl());

        return $payment->status === Payment::SUCCESS
            ? $back->with('success', 'Payment received – thank you!')
            : $back->with('error', 'The payment was not completed. You have not been charged; you can try again.');
    }

    /** Paystack's server-to-server notification. Signature-checked; always re-verified with the API. */
    public function webhook(Request $request, PaystackClient $paystack, PaymentService $payments)
    {
        $payload = $request->getContent();
        if (! $paystack->validWebhookSignature($payload, $request->header('x-paystack-signature'))) {
            return response('Invalid signature', 401);
        }

        $event = json_decode($payload, true);
        $reference = (string) data_get($event, 'data.reference', '');
        if (data_get($event, 'event') === 'charge.success' && $reference !== '') {
            try {
                $payments->confirm($reference);
            } catch (PaymentGatewayException $e) {
                Log::warning('Paystack webhook could not verify payment', ['reference' => $reference]);

                return response('Retry later', 503);
            }
        }

        return response('OK', 200);
    }
}
