<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalServiceTemplateRequest;
use App\Models\ProposalServiceTemplate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProposalServiceTemplateController extends Controller
{
    public function index(): View
    {
        $templates = ProposalServiceTemplate::query()
            ->withCount('items')
            ->ordered()
            ->get()
            ->groupBy(fn (ProposalServiceTemplate $template): string => $template->is_active ? 'active' : 'inactive');

        return view('admin.proposal-templates.index', [
            'activeTemplates' => $templates->get('active', collect()),
            'inactiveTemplates' => $templates->get('inactive', collect()),
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
            $this->syncItems($template, $request->validated('items'));

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
            $this->syncItems($proposalTemplate, $request->validated('items'));
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
                'is_active' => false,
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

    public function status(Request $request, ProposalServiceTemplate $proposalTemplate): RedirectResponse
    {
        $proposalTemplate->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.proposal-templates.index')->with('success', __('site.proposal_template_status_updated'));
    }

    private function payload(ProposalServiceTemplateRequest $request): array
    {
        return collect($request->validated())
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
    }

    private function syncItems(ProposalServiceTemplate $template, array $items): void
    {
        $template->items()->delete();

        collect($items)->values()->each(function (array $item, int $index) use ($template): void {
            $template->items()->create([
                'item_code' => $item['item_code'] ?? null,
                'description_en' => $item['description_en'],
                'description_es' => $item['description_es'],
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
