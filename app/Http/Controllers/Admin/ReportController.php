<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Enquiry;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Aggregate summaries of data the system actually stores. Exports contain counts only –
 * no names, emails or phone numbers.
 */
class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.index', ['from' => $from, 'to' => $to, ...$this->summary($from, $to)]);
    }

    public function export(Request $request, AuditLogger $audit)
    {
        [$from, $to] = $this->range($request);
        $s = $this->summary($from, $to);
        $audit->log('report.exported', null, ['from' => $from->toDateString(), 'to' => $to->toDateString()]);

        $rows = [['Report', 'Group', 'Item', 'Count']];
        foreach ($s['byStatus'] as $label => $count) {
            $rows[] = ['Appointment requests', 'Status', $label, $count];
        }
        foreach ($s['byBranch'] as $row) {
            $rows[] = ['Appointment requests', 'Branch', $row->label, $row->total];
        }
        foreach ($s['byService'] as $row) {
            $rows[] = ['Appointment requests', 'Service', $row->label, $row->total];
        }
        foreach ($s['enquiriesByStatus'] as $label => $count) {
            $rows[] = ['Enquiries', 'Status', $label, $count];
        }
        foreach ($s['productEnquiries'] as $row) {
            $rows[] = ['Catalogue activity', 'Product enquiries', $row->label, $row->total];
        }

        $filename = 'innes-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                // Neutralise spreadsheet formula injection.
                fputcsv($out, array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@]/', $v) ? "'".$v : $v, $row));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): array
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }

    private function summary(Carbon $from, Carbon $to): array
    {
        $appointments = Appointment::query()->whereBetween('appointments.created_at', [$from, $to]);

        $statusCounts = (clone $appointments)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $byStatus = collect(AppointmentStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => (int) ($statusCounts[$s->value] ?? 0)]);

        $byBranch = (clone $appointments)->join('branches', 'branches.id', '=', 'appointments.branch_id')
            ->selectRaw('branches.name as label, COUNT(*) as total')->groupBy('branches.name')->orderByDesc('total')->get();
        $byService = (clone $appointments)->join('services', 'services.id', '=', 'appointments.service_id')
            ->selectRaw('services.name as label, COUNT(*) as total')->groupBy('services.name')->orderByDesc('total')->get();

        $enquiries = Enquiry::query()->whereBetween('enquiries.created_at', [$from, $to]);
        $eCounts = (clone $enquiries)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $enquiriesByStatus = collect(EnquiryStatus::cases())->mapWithKeys(fn ($s) => [$s->label() => (int) ($eCounts[$s->value] ?? 0)]);

        $productEnquiries = (clone $enquiries)->join('products', 'products.id', '=', 'enquiries.product_id')
            ->selectRaw('products.name as label, COUNT(*) as total')->groupBy('products.name')->orderByDesc('total')->limit(15)->get();

        return [
            'totalAppointments' => $byStatus->sum(),
            'byStatus' => $byStatus,
            'byBranch' => $byBranch,
            'byService' => $byService,
            'totalEnquiries' => $enquiriesByStatus->sum(),
            'enquiriesByStatus' => $enquiriesByStatus,
            'productEnquiries' => $productEnquiries,
        ];
    }
}
