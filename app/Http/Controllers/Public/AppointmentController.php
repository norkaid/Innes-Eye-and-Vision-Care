<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use App\Services\PaymentService;
use App\Services\ShopService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    public function create(Request $request, AvailabilityService $availability)
    {
        $branches = Branch::active()->ordered()->with(['services' => fn ($q) => $q->published()])->get();
        $services = Service::published()->ordered()->get(['id', 'name', 'slug', 'booking_fee']);
        $shop = app(ShopService::class);
        $label = fn (array $c) => ['fee' => ShopService::money($c['fee']), 'deposit' => ShopService::money($c['deposit']), 'balance' => ShopService::money($c['balance']), 'percent' => $c['percent']];
        $charges = $services->mapWithKeys(fn ($s) => [$s->id => $label($shop->appointmentCharges($s))])->all();
        $charges['default'] = $label($shop->appointmentCharges(null));

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
            'charges' => $charges,
            'depositsOn' => $shop->appointmentPaymentsEnabled() && $shop->appointmentCharges(null)['percent'] > 0,
            'smsAvailable' => app(SmsService::class)->enabled(),
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
        $appointment = $result['appointment'];

        // The deposit is paid straight away on Paystack's page; the return page shows the request.
        if ($appointment->canBePaid()) {
            try {
                return redirect()->away(app(PaymentService::class)->start($appointment));
            } catch (PaymentGatewayException $e) {
                session()->flash('error', 'Your request was saved, but the payment page could not be opened: '.$e->getMessage());
            }
        }

        return redirect()->route('appointments.received')
            ->with('appointment_reference', $appointment->reference_code)
            ->with('appointment_acknowledged', $result['acknowledged'])
            ->with('appointment_payment', $appointment->canBePaid() ? ['url' => $appointment->paymentUrl(), 'amount' => (float) $appointment->amount_due] : null);
    }

    public function received(Request $request)
    {
        return view('public.appointments.received', [
            'reference' => $request->session()->get('appointment_reference'),
            'acknowledged' => (bool) $request->session()->get('appointment_acknowledged'),
            'payment' => $request->session()->get('appointment_payment'),
        ]);
    }
}
