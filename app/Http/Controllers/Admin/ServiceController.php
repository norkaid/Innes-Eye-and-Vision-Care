<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Service;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    public function __construct(private ImageUploader $images, private AuditLogger $audit) {}

    public function index(Request $request)
    {
        $archived = $request->boolean('archived');
        $services = Service::query()->when($archived, fn ($q) => $q->onlyTrashed())
            ->withCount('branches')->ordered()->paginate(25)->withQueryString();

        return view('admin.services.index', compact('services', 'archived'));
    }

    public function create()
    {
        return view('admin.services.form', ['service' => new Service(['is_published' => false]), 'branches' => Branch::ordered()->get(), 'selected' => []]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $service = DB::transaction(function () use ($data, $request) {
            $service = new Service($data);
            $service->image_path = $request->hasFile('image') ? $this->images->store($request->file('image'), 'services') : null;
            $service->save();
            $service->branches()->sync($data['branches'] ?? []);

            return $service;
        });
        $this->audit->log('service.created', $service, ['name' => $service->name]);

        return redirect()->route('admin.services.index')->with('success', "Service \"{$service->name}\" created.");
    }

    public function edit(Service $service)
    {
        return view('admin.services.form', ['service' => $service, 'branches' => Branch::ordered()->get(), 'selected' => $service->branches()->pluck('branches.id')->all()]);
    }

    public function update(Request $request, Service $service)
    {
        $data = $this->validated($request, $service);
        DB::transaction(function () use ($data, $request, $service) {
            $service->fill($data);
            $service->image_path = $this->images->replace($request->file('image'), $service->image_path, 'services', $request->boolean('remove_image'));
            $service->save();
            $service->branches()->sync($data['branches'] ?? []);
        });
        $this->audit->log('service.updated', $service, ['name' => $service->name, 'published' => $service->is_published]);

        return redirect()->route('admin.services.index')->with('success', "Service \"{$service->name}\" saved.");
    }

    /** Archive (soft delete). Existing appointments keep their reference to the service. */
    public function destroy(Service $service)
    {
        $service->is_published = false;
        $service->save();
        $service->delete();
        $this->audit->log('service.archived', $service, ['name' => $service->name]);

        return redirect()->route('admin.services.index')->with('success', "Service \"{$service->name}\" archived.");
    }

    public function restore(Service $service)
    {
        $service->restore();
        $this->audit->log('service.restored', $service);

        return redirect()->route('admin.services.index')->with('success', "Service \"{$service->name}\" restored as a draft.");
    }

    private function validated(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', Rule::unique('services', 'slug')->ignore($service?->id)],
            'short_description' => ['required', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:10000'],
            'preparation' => ['nullable', 'string', 'max:5000'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_published' => ['boolean'],
            'branches' => ['array'],
            'branches.*' => ['integer', 'exists:branches,id'],
            'image' => ImageUploader::rules(),
            'remove_image' => ['boolean'],
        ]);
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
