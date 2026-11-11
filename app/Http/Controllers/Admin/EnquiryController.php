<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\User;
use App\Services\EnquiryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(EnquiryStatus::class)],
            'assigned' => ['nullable', 'string', 'max:20'],
        ]);

        $enquiries = Enquiry::with(['assignee', 'product'])
            ->search($filters['q'] ?? null)
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->when(($filters['assigned'] ?? null) === 'me', fn ($q) => $q->where('assigned_to', $request->user()->id))
            ->when(($filters['assigned'] ?? null) === 'none', fn ($q) => $q->whereNull('assigned_to'))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.enquiries.index', compact('enquiries', 'filters'));
    }

    public function show(Enquiry $enquiry)
    {
        $enquiry->load(['notes.author', 'assignee', 'product', 'user']);

        return view('admin.enquiries.show', [
            'enquiry' => $enquiry,
            'staff' => $this->assignableStaff(),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry, EnquiryService $service)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
            'assigned_to' => ['nullable', 'integer', Rule::in($this->assignableStaff()->keys()->all())],
        ]);
        $service->update($enquiry, $data);

        return back()->with('success', 'Enquiry updated.');
    }

    public function note(Request $request, Enquiry $enquiry, EnquiryService $service)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:3000']]);
        $service->addNote($enquiry, $data['note'], $request->user());

        return back()->with('success', 'Internal note added.');
    }

    private function assignableStaff()
    {
        return User::staff()->where('is_active', true)->with('roles')->orderBy('first_name')->get()
            ->filter(fn (User $u) => $u->hasPermission('enquiries.manage'))
            ->mapWithKeys(fn (User $u) => [$u->id => $u->name]);
    }
}
