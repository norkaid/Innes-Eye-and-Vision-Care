<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $appointments = $request->user()->appointments()->with(['branch', 'service'])->latest()->paginate(10);

        return view('customer.appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment, AppointmentService $service, AvailabilityService $availability)
    {
        $this->authorize('view', $appointment);
        $appointment->load(['branch.openingHours', 'service']);

        return view('customer.appointments.show', [
            'appointment' => $appointment,
            'beforeCutoff' => $service->beforeCutoff($appointment),
            'minDate' => $availability->earliestBookable()->toDateString(),
            'maxDate' => $availability->latestBookableDate()->toDateString(),
        ]);
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $this->authorize('cancel', $appointment);
        $service->customerCancel($appointment, $request->user());

        return redirect()->route('account.appointments.show', $appointment)->with('success', 'Your appointment request has been cancelled.');
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentService $service, AvailabilityService $availability)
    {
        $this->authorize('reschedule', $appointment);
        $data = $request->validate([
            'requested_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'requested_time' => ['required', 'date_format:H:i'],
        ]);

        $appointment->loadMissing('branch.openingHours');
        $date = Carbon::parse($data['requested_date']);
        if (! $availability->isSlotAvailable($appointment->branch, $date, $data['requested_time'], $appointment->id)) {
            throw ValidationException::withMessages(['requested_time' => 'That time is not available. Please choose another available time.']);
        }

        $service->customerRequestReschedule($appointment, $data, $request->user());

        return redirect()->route('account.appointments.show', $appointment)
            ->with('success', 'Your reschedule request has been sent. The clinic will confirm the new time.');
    }
}
