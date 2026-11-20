<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\PaymentService;

/** Booking-fee payment for one appointment request, reached through a signed link. */
class AppointmentPaymentController extends Controller
{
    public function show(Appointment $appointment)
    {
        return view('public.appointments.pay', ['appointment' => $appointment->load('service', 'branch')]);
    }

    public function pay(Appointment $appointment, PaymentService $payments)
    {
        if (! $appointment->canBePaid()) {
            return redirect()->to($appointment->paymentUrl());
        }

        try {
            return redirect()->away($payments->start($appointment));
        } catch (PaymentGatewayException $e) {
            return redirect()->to($appointment->paymentUrl())->with('error', $e->getMessage());
        }
    }
}
