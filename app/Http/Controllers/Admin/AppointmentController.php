<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'branch' => ['nullable', 'integer'],
            'service' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $appointments = Appointment::with(['branch', 'service'])
            ->search($filters['q'] ?? null)
            ->status($filters['status'] ?? null)
            ->when($filters['branch'] ?? null, fn ($q, $v) => $q->where('branch_id', $v))
            ->when($filters['service'] ?? null, fn ($q, $v) => $q->where('service_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('requested_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('requested_date', '<=', $v))
            ->orderByRaw("CASE WHEN status IN ('pending','reschedule_requested') THEN 0 ELSE 1 END")
            ->orderBy('requested_date')
            ->paginate(20)->withQueryString();

        return view('admin.appointments.index', [
            'appointments' => $appointments,
            'filters' => $filters,
            'branches' => Branch::withTrashed()->ordered()->pluck('name', 'id'),
            'services' => Service::withTrashed()->ordered()->pluck('name', 'id'),
        ]);
    }

    public function show(Request $request, Appointment $appointment, AvailabilityService $availability)
    {
        $appointment->load(['branch.openingHours', 'service', 'user', 'handler', 'histories.changer']);
        $date = $request->filled('date') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->input('date'))
            ? Carbon::parse($request->input('date'))
            : ($appointment->proposed_start_at ?? $appointment->requested_date);
        $slots = $availability->availableSlots($appointment->branch, Carbon::parse($date->format('Y-m-d')), $appointment->id);

        return view('admin.appointments.show', [
            'appointment' => $appointment,
            'transitions' => $appointment->status->staffTransitions(),
            'slotDate' => $date->format('Y-m-d'),
            'slots' => $slots,
        ]);
    }

    public function update(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'start_date' => ['nullable', 'date_format:Y-m-d', Rule::requiredIf(fn () => in_array($request->input('status'), ['confirmed', 'reschedule_proposed'], true))],
            'start_time' => ['nullable', 'date_format:H:i', Rule::requiredIf(fn () => in_array($request->input('status'), ['confirmed', 'reschedule_proposed'], true))],
            'note' => ['nullable', 'string', 'max:1000'],
            'notify' => ['nullable', 'boolean'],
        ]);
        $data['notify'] = $request->boolean('notify');

        $updated = $service->changeStatus($appointment, $data, $request->user());

        return redirect()->route('admin.appointments.show', $updated)->with('success', 'Status updated to "'.$updated->status->label().'".');
    }

    public function note(Request $request, Appointment $appointment, AppointmentService $service)
    {
        $data = $request->validate(['staff_note' => ['nullable', 'string', 'max:5000']]);
        $service->updateStaffNote($appointment, $data['staff_note'] ?? null, $request->user());

        return back()->with('success', 'Internal note saved.');
    }
}
