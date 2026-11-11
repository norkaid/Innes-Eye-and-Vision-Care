<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Enquiry;
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
            $data['stats']['pending'] = Appointment::whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::RescheduleRequested->value])->count();
            $data['stats']['confirmed_upcoming'] = Appointment::where('status', AppointmentStatus::Confirmed->value)->where('confirmed_start_at', '>=', now())->count();
            $data['todays'] = Appointment::with(['branch', 'service'])->where('status', AppointmentStatus::Confirmed->value)
                ->whereBetween('confirmed_start_at', [today(), today()->endOfDay()])->orderBy('confirmed_start_at')->get();
            $data['awaiting'] = Appointment::with(['branch', 'service'])
                ->whereIn('status', [AppointmentStatus::Pending->value, AppointmentStatus::RescheduleRequested->value])
                ->oldest()->limit(6)->get();
        }
        if ($user->can('enquiries.view')) {
            $data['stats']['new_enquiries'] = Enquiry::where('status', EnquiryStatus::New->value)->count();
            $data['recentEnquiries'] = Enquiry::latest()->limit(5)->get();
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
