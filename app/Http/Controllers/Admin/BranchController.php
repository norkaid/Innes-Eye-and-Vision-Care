<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BranchController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $archived = $request->boolean('archived');
        $branches = Branch::query()->when($archived, fn ($q) => $q->onlyTrashed())->withCount('services')->ordered()->get();

        return view('admin.branches.index', compact('branches', 'archived'));
    }

    public function create()
    {
        return view('admin.branches.form', ['branch' => new Branch(['is_active' => true]), 'services' => Service::ordered()->get(), 'selected' => [], 'hours' => collect()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $branch = DB::transaction(function () use ($data) {
            $branch = Branch::create($data);
            $this->syncRelations($branch, $data);

            return $branch;
        });
        $this->audit->log('branch.created', $branch, ['name' => $branch->name]);

        return redirect()->route('admin.branches.index')->with('success', "Branch \"{$branch->name}\" created.");
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.form', [
            'branch' => $branch,
            'services' => Service::ordered()->get(),
            'selected' => $branch->services()->pluck('services.id')->all(),
            'hours' => $branch->openingHours->keyBy('day_of_week'),
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validated($request, $branch);
        DB::transaction(function () use ($branch, $data) {
            $branch->update($data);
            $this->syncRelations($branch, $data);
        });
        $this->audit->log('branch.updated', $branch, ['name' => $branch->name]);

        return redirect()->route('admin.branches.index')->with('success', "Branch \"{$branch->name}\" saved.");
    }

    public function destroy(Branch $branch)
    {
        $branch->is_active = false;
        $branch->save();
        $branch->delete();
        $this->audit->log('branch.archived', $branch, ['name' => $branch->name]);

        return redirect()->route('admin.branches.index')->with('success', "Branch \"{$branch->name}\" archived. It no longer appears on the website.");
    }

    public function restore(Branch $branch)
    {
        $branch->restore();
        $this->audit->log('branch.restored', $branch);

        return redirect()->route('admin.branches.index')->with('success', "Branch \"{$branch->name}\" restored (inactive).");
    }

    private function syncRelations(Branch $branch, array $data): void
    {
        $branch->services()->sync($data['services'] ?? []);
        foreach (Branch::DAYS as $day => $name) {
            $h = $data['hours'][$day] ?? [];
            $closed = ! empty($h['is_closed']) || empty($h['opens_at']) || empty($h['closes_at']);
            $branch->openingHours()->updateOrCreate(['day_of_week' => $day], [
                'is_closed' => $closed,
                'opens_at' => $closed ? null : $h['opens_at'],
                'closes_at' => $closed ? null : $h['closes_at'],
            ]);
        }
    }

    private function validated(Request $request, ?Branch $branch = null): array
    {
        $validator = validator($request->all(), [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique('branches', 'slug')->ignore($branch?->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'map_url' => ['nullable', 'url:https', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['boolean'],
            'services' => ['array'],
            'services.*' => ['integer', 'exists:services,id'],
            'hours' => ['array'],
            'hours.*.is_closed' => ['nullable', 'boolean'],
            'hours.*.opens_at' => ['nullable', 'date_format:H:i'],
            'hours.*.closes_at' => ['nullable', 'date_format:H:i'],
        ], [], ['map_url' => 'map link']);

        $validator->after(function (Validator $v) use ($request) {
            foreach ((array) $request->input('hours', []) as $day => $h) {
                if (! empty($h['is_closed']) || empty($h['opens_at']) || empty($h['closes_at'])) {
                    continue;
                }
                if ($h['closes_at'] <= $h['opens_at']) {
                    $v->errors()->add("hours.$day.closes_at", (Branch::DAYS[$day] ?? 'Day').': closing time must be after opening time.');
                }
            }
        });

        $data = $validator->validate();
        $data['is_active'] = $request->boolean('is_active');
        $data['display_order'] = $data['display_order'] ?? 0;

        return $data;
    }
}
