<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Service;
use App\Services\Services\ServiceDeliverableNormalizer;
use App\Services\Services\ServiceContentTranslator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $this->syncDeliverables($service, $request->validated('deliverables') ?? []);

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
        $this->syncDeliverables($service, $request->validated('deliverables') ?? []);

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.service_updated'));
    }

    public function translate(Request $request, Service $service, ServiceContentTranslator $translator): RedirectResponse
    {
        $source = $request->input('source_locale') === 'es' ? 'es' : 'en';
        $target = $source === 'es' ? 'en' : 'es';
        $overwrite = $request->boolean('overwrite');
        $payload = $request->only([
            'name_en',
            'name_es',
            'description_en',
            'description_es',
            'deliverables',
        ]);

        try {
            foreach (['name', 'description'] as $field) {
                $sourceKey = "{$field}_{$source}";
                $targetKey = "{$field}_{$target}";

                if (! filled($payload[$sourceKey] ?? null) || (filled($payload[$targetKey] ?? null) && ! $overwrite)) {
                    continue;
                }

                $payload[$targetKey] = $translator->translate($payload[$sourceKey], $source, $target);
            }

            $payload['deliverables'] = collect($payload['deliverables'] ?? [])
                ->map(function (array $row) use ($source, $target, $overwrite, $translator): array {
                    if (filled($row[$source] ?? null) && (! filled($row[$target] ?? null) || $overwrite)) {
                        $row[$target] = $translator->translate($row[$source], $source, $target);
                    }

                    return $row;
                })
                ->all();
        } catch (\Throwable) {
            return redirect()
                ->route('admin.services.edit', $service)
                ->withInput($request->all())
                ->withErrors(['translation' => __('site.service_translation_failed')]);
        }

        return redirect()
            ->route('admin.services.edit', $service)
            ->withInput($payload)
            ->with('success', __('site.service_translation_ready'));
    }

    private function payload(ServiceRequest $request): array
    {
        $deliverables = $this->deliverableRows($request->validated('deliverables') ?? []);
        $nameEn = trim((string) $request->validated('name_en'));
        $nameEs = trim((string) $request->validated('name_es'));
        $legacyName = trim((string) $request->validated('name'));
        $name = $nameEn ?: ($nameEs ?: $legacyName);

        return [
            'name' => $name,
            'name_en' => $nameEn ?: null,
            'name_es' => $nameEs ?: null,
            'slug' => Str::slug($name),
            'code' => Str::upper($request->validated('code')),
            'business_line' => $request->validated('business_line'),
            'service_type' => $request->validated('service_type'),
            'service_scope' => $request->validated('service_scope'),
            'description' => $request->validated('description_en') ?: ($request->validated('description_es') ?: $request->validated('description')),
            'description_en' => $request->validated('description_en'),
            'description_es' => $request->validated('description_es'),
            'deliverables_schema' => collect($deliverables)
                ->map(fn (array $row): string => $row['en'] ?: $row['es'])
                ->values()
                ->all(),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    private function syncDeliverables(Service $service, array $rawDeliverables): void
    {
        $deliverables = $this->deliverableRows($rawDeliverables);

        $service->deliverables()->delete();

        collect($deliverables)->each(function (array $row, int $index) use ($service): void {
            $name = $row['en'] ?: $row['es'];

            $service->deliverables()->create([
                'name' => $name,
                'name_en' => $row['en'] ?: null,
                'name_es' => $row['es'] ?: null,
                'description' => null,
                'sort_order' => $index + 1,
                'is_active' => true,
                'is_client_visible_by_default' => true,
            ]);
        });
    }

    private function deliverableRows(array $rows): array
    {
        return app(ServiceDeliverableNormalizer::class)->rows($rows);
    }
}
