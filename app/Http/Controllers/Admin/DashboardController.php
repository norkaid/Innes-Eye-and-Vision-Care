<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\EnquiryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Enquiry;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\Request;

/** Every figure is calculated from real records, and only shown to roles allowed to see it. */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $data = ['stats' => []];

        if ($user->can('appointments.view')) {
            // Requests still waiting for their deposit are counted separately: they cannot be confirmed yet.
            $data['stats']['pending'] = Appointment::whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::RescheduleRequested->value])
                ->where('payment_status', '!=', PaymentStatus::Unpaid->value)->count();
            $data['stats']['awaiting_deposit'] = Appointment::where('status', AppointmentStatus::Pending->value)->where('payment_status', PaymentStatus::Unpaid->value)->count();
            $data['stats']['confirmed_upcoming'] = Appointment::where('status', AppointmentStatus::Confirmed->value)->where('confirmed_start_at', '>=', now())->count();
            $data['todays'] = Appointment::with(['branch', 'service'])->where('status', AppointmentStatus::Confirmed->value)
                ->whereBetween('confirmed_start_at', [today(), today()->endOfDay()])->orderBy('confirmed_start_at')->get();
            $data['awaiting'] = Appointment::with(['branch', 'service'])
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::RescheduleRequested->value])
                ->where('payment_status', '!=', PaymentStatus::Unpaid->value)
                ->oldest()->limit(6)->get();
        }
        if ($user->can('enquiries.view')) {
            $data['stats']['new_enquiries'] = Enquiry::where('status', EnquiryStatus::New->value)->count();
            $data['recentEnquiries'] = Enquiry::latest()->limit(5)->get();
        }
        if ($user->can('orders.view')) {
            $data['stats']['orders_to_prepare'] = Order::whereIn('status', [OrderStatus::Paid->value, OrderStatus::Processing->value])->count();
        }
        if ($user->can('payments.view')) {
            $data['stats']['paid_this_month'] = Payment::where('status', Payment::SUCCESS)->where('paid_at', '>=', now()->startOfMonth())->sum('amount_minor') / 100;
        }
        if ($user->can('services.manage')) {
            $data['stats']['services'] = Service::published()->count();
        }
        if ($user->can('products.manage')) {
            $data['stats']['products'] = Product::where('is_published', true)->count();
        }
        if ($user->can('audit.view')) {
            $data['activity'] = AuditLog::with('user')->latest('id')->limit(8)->get();
        }

        return view('admin.dashboard', $data);
    }
}
