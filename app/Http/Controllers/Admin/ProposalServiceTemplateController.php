<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalServiceTemplateRequest;
use App\Models\ProposalServiceTemplate;
use App\Services\Services\ServiceContentTranslator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposalServiceTemplateController extends Controller
{
    public function index(): View
    {
        $templates = ProposalServiceTemplate::query()
            ->withCount('items')
            ->ordered()
            ->get();

        return view('admin.proposal-templates.index', [
            'templates' => $templates,
        ]);
    }

    public function create(): View
    {
        return view('admin.proposal-templates.create', [
            'proposalTemplate' => new ProposalServiceTemplate([
                'service_number' => ((int) (ProposalServiceTemplate::query()->max('service_number') ?? 0)) + 1,
                'sort_order' => ((int) (ProposalServiceTemplate::query()->max('sort_order') ?? 0)) + 1,
                'is_active' => true,
            ]),
            'items' => [
                ['item_code' => '', 'description_en' => '', 'description_es' => '', 'unit' => '', 'quantity' => '', 'unit_value' => ''],
            ],
        ]);
    }

    public function store(ProposalServiceTemplateRequest $request): RedirectResponse
    {
        $template = DB::transaction(function () use ($request): ProposalServiceTemplate {
            $template = ProposalServiceTemplate::query()->create($this->payload($request));
            $this->syncItems($template, $request->validated('items'), $request->input('content_locale') === 'es' ? 'es' : 'en');

            return $template;
        });

        return redirect()->route('admin.proposal-templates.edit', $template)->with('success', __('site.proposal_template_created'));
    }

    public function edit(ProposalServiceTemplate $proposalTemplate): View
    {
        return view('admin.proposal-templates.edit', [
            'proposalTemplate' => $proposalTemplate->load('items'),
            'items' => $this->itemRows($proposalTemplate),
        ]);
    }

    public function update(ProposalServiceTemplateRequest $request, ProposalServiceTemplate $proposalTemplate): RedirectResponse
    {
        DB::transaction(function () use ($request, $proposalTemplate): void {
            $proposalTemplate->update($this->payload($request));
            $this->syncItems($proposalTemplate, $request->validated('items'), $request->input('content_locale') === 'es' ? 'es' : 'en');
        });

        return redirect()->route('admin.proposal-templates.edit', $proposalTemplate)->with('success', __('site.proposal_template_updated'));
    }

    public function duplicate(ProposalServiceTemplate $proposalTemplate): RedirectResponse
    {
        $copy = DB::transaction(function () use ($proposalTemplate): ProposalServiceTemplate {
            $proposalTemplate->load('items');
            $copy = ProposalServiceTemplate::query()->create([
                'code' => $this->copyCode($proposalTemplate->code),
                'service_number' => ((int) (ProposalServiceTemplate::query()->max('service_number') ?? 0)) + 1,
                'name_en' => Str::limit($proposalTemplate->name_en.' Copy', 255, ''),
                'name_es' => Str::limit($proposalTemplate->name_es.' copia', 255, ''),
                'landing_title_en' => Str::limit(($proposalTemplate->landing_title_en ?: $proposalTemplate->name_en).' Copy', 255, ''),
                'landing_title_es' => Str::limit(($proposalTemplate->landing_title_es ?: $proposalTemplate->name_es).' copia', 255, ''),
                'landing_description_en' => $proposalTemplate->landing_description_en,
                'landing_description_es' => $proposalTemplate->landing_description_es,
                'is_active' => true,
                'sort_order' => ((int) (ProposalServiceTemplate::query()->max('sort_order') ?? 0)) + 1,
            ]);

            $proposalTemplate->items->each(function ($item) use ($copy): void {
                $copy->items()->create([
                    'item_code' => $item->item_code,
                    'description_en' => $item->description_en,
                    'description_es' => $item->description_es,
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'unit_value' => $item->unit_value,
                    'sort_order' => $item->sort_order,
                ]);
            });

            return $copy;
        });

        return redirect()->route('admin.proposal-templates.edit', $copy)->with('success', __('site.proposal_template_duplicated'));
    }

    public function destroy(ProposalServiceTemplate $proposalTemplate): RedirectResponse
    {
        DB::transaction(function () use ($proposalTemplate): void {
            $proposalTemplate->delete();
        });

        return redirect()->route('admin.proposal-templates.index')->with('success', __('site.proposal_template_deleted'));
    }

    private function payload(ProposalServiceTemplateRequest $request): array
    {
        $payload = collect($request->validated())
            ->only([
                'code',
                'service_number',
                'name_en',
                'name_es',
                'landing_title_en',
                'landing_title_es',
                'landing_description_en',
                'landing_description_es',
                'sort_order',
                'is_active',
            ])
            ->all();

        $contentLocale = $request->input('content_locale') === 'es' ? 'es' : 'en';
        $translator = app(ServiceContentTranslator::class);
        $sourceKey = "name_{$contentLocale}";
        $targetKey = $contentLocale === 'es' ? 'name_en' : 'name_es';
        $source = trim((string) ($payload[$sourceKey] ?? ''));
        $target = trim((string) ($payload[$targetKey] ?? ''));

        if (! $translator->isUsableTranslation($source, $target)) {
            try {
                $translated = $translator->translate($source, $contentLocale, $contentLocale === 'es' ? 'en' : 'es');
                $payload[$targetKey] = $translator->isUsableTranslation($source, $translated) ? $translated : '';
            } catch (\Throwable) {
                $payload[$targetKey] = '';
                session()->flash('warning', __('site.dynamic_translation_unavailable'));
            }
        }

        $payload['landing_title_en'] = $payload['name_en'] ?? '';
        $payload['landing_title_es'] = $payload['name_es'] ?? '';

        return $payload;
    }

    private function syncItems(ProposalServiceTemplate $template, array $items, string $contentLocale): void
    {
        $template->items()->delete();
        $translator = app(ServiceContentTranslator::class);

        collect($items)->values()->each(function (array $item, int $index) use ($template, $contentLocale, $translator): void {
            if ($contentLocale === 'en') {
                $item['description_es'] = $this->cachedTranslation($translator, $item['description_en'] ?? null, 'en', 'es', $item['description_es'] ?? null);
            } else {
                $item['description_en'] = $this->cachedTranslation($translator, $item['description_es'] ?? null, 'es', 'en', $item['description_en'] ?? null);
            }

            $template->items()->create([
                'item_code' => $item['item_code'] ?? null,
                'description_en' => $item['description_en'] ?? '',
                'description_es' => $item['description_es'] ?? '',
                'unit' => $item['unit'] ?? null,
                'quantity' => $item['quantity'] ?? null,
                'unit_value' => $item['unit_value'] ?? null,
                'sort_order' => $index + 1,
            ]);
        });
    }

    private function itemRows(ProposalServiceTemplate $template): array
    {
        return $template->items
            ->map(fn ($item): array => [
                'item_code' => $item->item_code,
                'description_en' => $item->description_en,
                'description_es' => $item->description_es,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_value' => $item->unit_value,
            ])
            ->all();
    }

    private function cachedTranslation(ServiceContentTranslator $translator, ?string $source, string $sourceLocale, string $targetLocale, ?string $existing): ?string
    {
        $source = trim((string) $source);

        if ($source === '') {
            return $translator->isUsableTranslation('', $existing) ? $existing : null;
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

    private function copyCode(string $code): string
    {
        $base = Str::limit(Str::upper($code).'-COPY', 32, '');
        $candidate = $base;
        $index = 2;

        while (ProposalServiceTemplate::query()->where('code', $candidate)->exists()) {
            $candidate = Str::limit($base.'-'.$index, 40, '');
            $index++;
        }

        return $candidate;
    }
}
