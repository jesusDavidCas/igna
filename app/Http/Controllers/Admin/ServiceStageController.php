<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceStageRequest;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Services\Services\ServiceContentTranslator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceStageController extends Controller
{
    public function store(ServiceStageRequest $request, Service $service): RedirectResponse
    {
        $service->stages()->create([
            ...$this->payload($request),
            'is_active' => $request->boolean('is_active', true),
            'is_client_visible' => $request->boolean('is_client_visible', true),
        ]);

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.stage_created'));
    }

    public function update(ServiceStageRequest $request, Service $service, ServiceStage $stage): RedirectResponse
    {
        abort_unless($stage->service_id === $service->id, 404);

        $stage->update([
            ...$this->payload($request),
            'is_active' => $request->boolean('is_active'),
            'is_client_visible' => $request->boolean('is_client_visible'),
        ]);

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.stage_updated'));
    }

    public function destroy(Service $service, ServiceStage $stage): RedirectResponse
    {
        abort_unless($stage->service_id === $service->id, 404);

        if ($stage->ticketStageEvents()->exists()) {
            return redirect()
                ->route('admin.services.edit', $service)
                ->withErrors(['stage' => __('site.stage_cannot_delete_in_use')]);
        }

        $stage->delete();

        return redirect()->route('admin.services.edit', $service)->with('success', __('site.stage_deleted'));
    }

    public function translate(Request $request, Service $service, ServiceStage $stage, ServiceContentTranslator $translator): RedirectResponse
    {
        abort_unless($stage->service_id === $service->id, 404);

        $source = $request->input('source_locale') === 'es' ? 'es' : 'en';
        $target = $source === 'es' ? 'en' : 'es';
        $overwrite = $request->boolean('overwrite');
        $payload = $request->only([
            'name_en',
            'name_es',
            'code',
            'description_en',
            'description_es',
            'sort_order',
            'is_active',
            'is_client_visible',
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
        } catch (\Throwable) {
            return redirect()
                ->route('admin.services.edit', $service)
                ->withInput($request->all() + ['editing_stage_id' => $stage->id])
                ->withErrors(['stage_translation' => __('site.service_translation_failed')]);
        }

        return redirect()
            ->route('admin.services.edit', $service)
            ->withInput($payload + ['editing_stage_id' => $stage->id])
            ->with('success', __('site.service_translation_ready'));
    }

    private function payload(ServiceStageRequest $request): array
    {
        $nameEn = trim((string) $request->validated('name_en'));
        $nameEs = trim((string) $request->validated('name_es'));
        $legacyName = trim((string) $request->validated('name'));
        $name = $nameEn ?: ($nameEs ?: $legacyName);

        return [
            'name' => $name,
            'name_en' => $nameEn ?: null,
            'name_es' => $nameEs ?: null,
            'code' => Str::upper((string) $request->validated('code')),
            'description' => $request->validated('description_en') ?: ($request->validated('description_es') ?: $request->validated('description')),
            'description_en' => $request->validated('description_en'),
            'description_es' => $request->validated('description_es'),
            'sort_order' => $request->validated('sort_order'),
        ];
    }
}
