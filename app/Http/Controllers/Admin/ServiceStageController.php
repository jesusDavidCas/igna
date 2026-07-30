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
            ...$this->payload($request, $stage),
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

    private function payload(ServiceStageRequest $request, ?ServiceStage $stage = null): array
    {
        $contentLocale = $request->input('content_locale') === 'es' ? 'es' : 'en';
        $translator = app(ServiceContentTranslator::class);
        $nameEn = trim((string) $request->validated('name_en'));
        $nameEs = trim((string) $request->validated('name_es'));
        $descriptionEn = trim((string) $request->validated('description_en'));
        $descriptionEs = trim((string) $request->validated('description_es'));

        if ($contentLocale === 'en') {
            $nameEs = $this->cachedTranslation($translator, $nameEn, 'en', 'es', $stage?->name_es, $nameEs);
            $descriptionEs = $this->cachedTranslation($translator, $descriptionEn, 'en', 'es', $stage?->description_es, $descriptionEs);
        } else {
            $nameEn = $this->cachedTranslation($translator, $nameEs, 'es', 'en', $stage?->name_en, $nameEn);
            $descriptionEn = $this->cachedTranslation($translator, $descriptionEs, 'es', 'en', $stage?->description_en, $descriptionEn);
        }

        $legacyName = trim((string) $request->validated('name'));
        $name = $nameEn ?: ($nameEs ?: $legacyName);

        return [
            'name' => $name,
            'name_en' => $nameEn ?: null,
            'name_es' => $nameEs ?: null,
            'code' => Str::upper((string) $request->validated('code')),
            'description' => $descriptionEn ?: ($descriptionEs ?: $request->validated('description')),
            'description_en' => $descriptionEn ?: null,
            'description_es' => $descriptionEs ?: null,
            'sort_order' => $request->validated('sort_order'),
        ];
    }

    private function cachedTranslation(ServiceContentTranslator $translator, ?string $source, string $sourceLocale, string $targetLocale, ?string $existing, ?string $submitted = null): ?string
    {
        $source = trim((string) $source);
        $existing = trim((string) $existing);
        $submitted = trim((string) $submitted);

        if ($source === '') {
            return $translator->isUsableTranslation('', $existing) ? $existing : null;
        }

        if ($translator->isUsableTranslation($source, $submitted)) {
            return $submitted;
        }

        if ($translator->isUsableTranslation($source, $existing)) {
            return $existing;
        }

        try {
            $translated = $translator->translate($source, $sourceLocale, $targetLocale);

            if ($translator->isUsableTranslation($source, $translated)) {
                return $translated;
            }
        } catch (\Throwable) {
            session()->flash('warning', __('site.dynamic_translation_unavailable'));
        }

        return null;
    }
}
