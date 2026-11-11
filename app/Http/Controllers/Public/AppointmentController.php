<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    public function create(Request $request, AvailabilityService $availability)
    {
        $branches = Branch::active()->ordered()->with(['services' => fn ($q) => $q->published()])->get();
        $services = Service::published()->ordered()->get(['id', 'name', 'slug']);

        $branchServices = $branches->mapWithKeys(fn ($b) => [$b->id => $b->services->pluck('id')->all()]);
        $preService = $services->firstWhere('slug', $request->string('service')->toString());
        $preBranch = $branches->firstWhere('slug', $request->string('branch')->toString());
        $user = $request->user()?->isCustomer() ? $request->user() : null;

        return view('public.appointments.create', [
            'branches' => $branches,
            'services' => $services,
            'branchServices' => $branchServices,
            'preService' => $preService,
            'preBranch' => $preBranch,
            'minDate' => $availability->earliestBookable()->toDateString(),
            'maxDate' => $availability->latestBookableDate()->toDateString(),
            'windowDays' => (int) today()->diffInDays($availability->latestBookableDate()),
            'user' => $user,
        ]);
    }

    public function availability(Request $request, AvailabilityService $availability): JsonResponse
    {
        $data = $request->validate([
            'branch' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $branch = Branch::active()->with('openingHours')->find($data['branch']);
        if (! $branch) {
            return response()->json(['slots' => [], 'message' => 'Please choose a branch.']);
        }
        $date = Carbon::parse($data['date']);
        if (! $availability->isBookableDate($branch, $date)) {
            return response()->json(['slots' => [], 'message' => 'This branch is closed or not taking requests on that date. Please choose another date.']);
        }
        $slots = $availability->availableSlots($branch, $date);

        return response()->json([
            'slots' => $slots,
            'message' => $slots ? '' : 'No times are available on this date. Please choose another date.',
        ]);
    }

    public function store(StoreAppointmentRequest $request, AppointmentService $appointments)
    {
        $result = $appointments->createRequest($request->appointmentData(), $request->user());

        // Only the reference is passed on – never the internal ID or customer details.
        return redirect()->route('appointments.received')
            ->with('appointment_reference', $result['appointment']->reference_code)
            ->with('appointment_acknowledged', $result['acknowledged']);
    }

    public function received(Request $request)
    {
        return view('public.appointments.received', [
            'reference' => $request->session()->get('appointment_reference'),
            'acknowledged' => (bool) $request->session()->get('appointment_acknowledged'),
        ]);
    }
}
