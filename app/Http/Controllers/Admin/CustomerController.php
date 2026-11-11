<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Registered customer accounts only. No clinical records are held. */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->limit(100)->toString();
        $customers = User::customers()->search($q ?: null)->withCount('appointments')->latest()->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers', 'q'));
    }

    public function show(Request $request, User $customer)
    {
        $this->ensureCustomer($customer);
        $appointments = $customer->appointments()->with(['branch', 'service'])->latest()->limit(50)->get();
        $enquiries = $request->user()->can('enquiries.view') ? $customer->enquiries()->latest()->limit(20)->get() : collect();

        return view('admin.customers.show', compact('customer', 'appointments', 'enquiries'));
    }

    public function edit(User $customer)
    {
        $this->ensureCustomer($customer);

        return view('admin.customers.edit', compact('customer'));
    }

    public function update(Request $request, User $customer, AuditLogger $audit)
    {
        $this->ensureCustomer($customer);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($customer->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
        ]);
        $customer->fill($data)->save();
        $audit->log('customer.updated', $customer, ['fields' => array_keys($customer->getChanges())]);

        return redirect()->route('admin.customers.show', $customer)->with('success', 'Customer details updated.');
    }

    public function toggle(User $customer, AuditLogger $audit)
    {
        $this->ensureCustomer($customer);
        $customer->is_active = ! $customer->is_active;
        $customer->save();
        $audit->log($customer->is_active ? 'customer.activated' : 'customer.deactivated', $customer);

        return back()->with('success', $customer->is_active ? 'Account reactivated.' : 'Account deactivated. The customer can no longer sign in.');
    }

    private function ensureCustomer(User $user): void
    {
        abort_unless($user->isCustomer(), 404);
    }
}
