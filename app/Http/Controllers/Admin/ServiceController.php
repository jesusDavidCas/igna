<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::query()
            ->with('stages')
            ->orderBy('business_line')
            ->orderBy('service_type')
            ->orderBy('service_scope')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('business_line');

        return view('admin.services.index', [
            'servicesByLine' => $services,
            'serviceTypeLabels' => collect(config('igna.service_types'))->flatMap(fn (array $types): array => $types)->map(fn (string $key): string => __($key)),
            'serviceScopeLabels' => collect(config('igna.service_scopes'))->map(fn (string $key): string => __($key))->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.create', [
            'service' => new Service([
                'business_line' => 'digital',
                'service_type' => 'web_platform',
                'service_scope' => 'none',
                'is_active' => true,
            ]),
            'serviceTypes' => config('igna.service_types'),
            'serviceScopes' => config('igna.service_scopes'),
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::query()->create($this->payload($request) + [
            'sort_order' => (Service::query()->max('sort_order') ?? 0) + 1,
        ]);
        $this->syncDeliverables($service, $request->validated('deliverables'));

        return redirect()->route('admin.services.index')->with('success', __('site.service_created'));
    }

    public function edit(Service $service): View
    {
        $service->load([
            'stages' => fn ($query) => $query->orderBy('sort_order'),
            'deliverables',
        ]);

        return view('admin.services.edit', [
            'service' => $service,
            'serviceTypes' => config('igna.service_types'),
            'serviceScopes' => config('igna.service_scopes'),
        ]);
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($this->payload($request));
        $this->syncDeliverables($service, $request->validated('deliverables'));

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.service_updated'));
    }

    private function payload(ServiceRequest $request): array
    {
        $deliverables = collect(preg_split('/\r\n|\r|\n/', (string) $request->validated('deliverables')))
            ->map(fn (string $deliverable): string => trim($deliverable))
            ->filter()
            ->values()
            ->all();

        return [
            'name' => $request->validated('name'),
            'slug' => Str::slug($request->validated('name')),
            'code' => Str::upper($request->validated('code')),
            'business_line' => $request->validated('business_line'),
            'service_type' => $request->validated('service_type'),
            'service_scope' => $request->validated('service_scope'),
            'description' => $request->validated('description'),
            'deliverables_schema' => $deliverables,
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncDeliverables(Service $service, ?string $rawDeliverables): void
    {
        $deliverables = collect(preg_split('/\r\n|\r|\n/', (string) $rawDeliverables))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values();

        $service->deliverables()->delete();

        $deliverables->each(function (string $line, int $index) use ($service): void {
            [$name, $description] = array_pad(array_map('trim', explode('|', $line, 2)), 2, null);

            $service->deliverables()->create([
                'name' => $name,
                'description' => $description,
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_client_visible_by_default' => true,
            ]);
        });
    }
}
