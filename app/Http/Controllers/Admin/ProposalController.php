<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalRequest;
use App\Models\Proposal;
use App\Models\ProposalServiceTemplate;
use App\Models\User;
use App\Support\Proposals\ProposalNumberGenerator;
use App\Support\Settings\BrandSettings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ProposalController extends Controller
{
    public function index(): View
    {
        return view('admin.proposals.index', [
            'proposals' => Proposal::query()->with('client')->latest()->paginate(15),
        ]);
    }

    public function create(): View
    {
        $proposalTemplates = $this->proposalTemplates();

        return view('admin.proposals.create', [
            'proposal' => new Proposal([
                'status' => 'draft',
                'tax_rate' => 0,
                'issued_at' => now(),
                'validity_days' => 30,
                'timeline_months' => 1,
                'timeline_weeks' => 0,
            ]),
            'clients' => $this->clients(),
            'signers' => $this->signers(),
            'selectedClientId' => request('client_user_id'),
            'selectedSignerId' => null,
            'paymentSchedule' => [
                ['label' => __('site.payment_start'), 'percentage' => '50'],
                ['label' => __('site.payment_delivery'), 'percentage' => '50'],
            ],
            'items' => $this->emptyItems(),
            'proposalTemplates' => $proposalTemplates,
            'proposalTemplatePayload' => $this->proposalTemplatePayload($proposalTemplates),
        ]);
    }

    public function store(ProposalRequest $request, ProposalNumberGenerator $numberGenerator): RedirectResponse
    {
        $proposal = DB::transaction(function () use ($request, $numberGenerator): Proposal {
            $proposal = Proposal::query()->create([
                ...$this->payload($request),
                'proposal_number' => $numberGenerator->generate(),
                'created_by_user_id' => $request->user()->id,
            ]);

            $this->syncItems($proposal, $request->validated('items'));

            return $proposal;
        });

        return redirect()->route('admin.proposals.show', $proposal)->with('success', __('site.proposal_created'));
    }

    public function show(Proposal $proposal, BrandSettings $brandSettings): View
    {
        $proposal->load(['client', 'createdBy', 'signer', 'items']);

        return view('admin.proposals.show', [
            'proposal' => $proposal,
            'brand' => $brandSettings->pdfPayload(),
            'clients' => $this->clients(),
            'proposalAccessUrl' => URL::signedRoute('proposals.public.show', $proposal),
        ]);
    }

    public function pdf(Proposal $proposal, BrandSettings $brandSettings): Response
    {
        $proposal->load(['client', 'createdBy', 'signer', 'items']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('admin.proposals.pdf', [
            'proposal' => $proposal,
            'brand' => $brandSettings->pdfPayload(),
        ])->render());
        $dompdf->setPaper('a4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$proposal->proposal_number.'.pdf"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    public function edit(Proposal $proposal): View
    {
        $proposalTemplates = $this->proposalTemplates();

        return view('admin.proposals.edit', [
            'proposal' => $proposal->load('items'),
            'clients' => $this->clients(),
            'signers' => $this->signers(),
            'selectedClientId' => $proposal->client_user_id,
            'selectedSignerId' => $proposal->signer_user_id,
            'paymentSchedule' => $proposal->paymentScheduleRows() ?: [
                ['label' => __('site.payment_start'), 'percentage' => '50'],
                ['label' => __('site.payment_delivery'), 'percentage' => '50'],
            ],
            'items' => $proposal->items->map(fn ($item): array => [
                'category' => $item->category,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_value' => $item->unit_value,
            ])->all(),
            'proposalTemplates' => $proposalTemplates,
            'proposalTemplatePayload' => $this->proposalTemplatePayload($proposalTemplates),
        ]);
    }

    public function update(ProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        DB::transaction(function () use ($request, $proposal): void {
            $proposal->update($this->payload($request));
            $proposal->items()->delete();
            $this->syncItems($proposal, $request->validated('items'));
        });

        return redirect()->route('admin.proposals.show', $proposal)->with('success', __('site.proposal_updated'));
    }

    private function clients()
    {
        return User::query()
            ->where('role', UserRole::CLIENT)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function signers()
    {
        return User::query()
            ->whereIn('role', [UserRole::SUPER_ADMIN->value, UserRole::ADMIN->value])
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    private function payload(ProposalRequest $request): array
    {
        $items = collect($request->validated('items'));
        $subtotal = $items->sum(fn (array $item): float => (float) ($item['quantity'] ?? 0) * (float) ($item['unit_value'] ?? 0));
        $taxRate = (float) $request->validated('tax_rate');
        $taxTotal = round($subtotal * ($taxRate / 100), 2);
        $timelineMonths = (int) $request->validated('timeline_months');
        $timelineWeeks = (int) $request->validated('timeline_weeks');
        $paymentSchedule = collect($request->validated('payment_schedule'))
            ->map(fn (array $payment): array => [
                'label' => $payment['label'] ?? null,
                'percentage' => round((float) $payment['percentage'], 2),
                'notes' => $payment['notes'] ?? null,
            ])
            ->values()
            ->all();
        $validityDays = (int) $request->validated('validity_days');
        $issuedAt = $request->validated('issued_at');
        $validUntil = $request->validated('valid_until') ?: ($issuedAt ? Carbon::parse($issuedAt)->addDays($validityDays)->toDateString() : null);

        return [
            'client_user_id' => $request->validated('client_user_id'),
            'signer_user_id' => $request->validated('signer_user_id'),
            'title' => $request->validated('title'),
            'subject' => $request->validated('subject'),
            'description' => $request->validated('description'),
            'scope' => $request->validated('scope'),
            'timeline_months' => $timelineMonths,
            'timeline_weeks' => $timelineWeeks,
            'timeline' => $this->timelineText($timelineMonths, $timelineWeeks),
            'payment_plan' => $this->paymentPlanText($paymentSchedule),
            'payment_schedule' => $paymentSchedule,
            'status' => $request->validated('status'),
            'tax_rate' => $taxRate,
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'total' => $subtotal + $taxTotal,
            'issued_at' => $issuedAt,
            'valid_until' => $validUntil,
            'validity_days' => $validityDays,
        ];
    }

    private function syncItems(Proposal $proposal, array $items): void
    {
        foreach ($items as $index => $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitValue = (float) ($item['unit_value'] ?? 0);

            $proposal->items()->create([
                'category' => $item['category'] ?? null,
                'item_code' => $item['item_code'] ?? null,
                'description' => $item['description'],
                'unit' => $item['unit'] ?? null,
                'quantity' => $quantity,
                'unit_value' => $unitValue,
                'subtotal' => $quantity * $unitValue,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function timelineText(int $months, int $weeks): string
    {
        $parts = [];

        if ($months > 0) {
            $parts[] = trans_choice('site.timeline_month_count', $months, ['count' => $months]);
        }

        if ($weeks > 0) {
            $parts[] = trans_choice('site.timeline_week_count', $weeks, ['count' => $weeks]);
        }

        return implode(' + ', $parts);
    }

    private function paymentPlanText(array $payments): string
    {
        return collect($payments)
            ->map(fn (array $payment): string => trim(($payment['label'] ?? __('site.payment_installment')).' - '.number_format((float) $payment['percentage'], 2).'%'.(filled($payment['notes'] ?? null) ? ' · '.$payment['notes'] : '')))
            ->implode("\n");
    }

    private function emptyItems(): array
    {
        return [
            ['category' => '', 'item_code' => '', 'description' => '', 'unit' => '', 'quantity' => '', 'unit_value' => ''],
            ['category' => '', 'item_code' => '', 'description' => '', 'unit' => '', 'quantity' => '', 'unit_value' => ''],
        ];
    }

    private function proposalTemplates(): Collection
    {
        return ProposalServiceTemplate::query()
            ->with('items')
            ->active()
            ->ordered()
            ->get();
    }

    private function proposalTemplatePayload(Collection $templates): array
    {
        return $templates
            ->mapWithKeys(fn (ProposalServiceTemplate $template): array => [
                $template->id => [
                    'label' => sprintf('%02d · %s', $template->service_number, $template->localizedName()),
                    'items' => $template->items->map(fn ($item): array => [
                        'category' => '',
                        'item_code' => $item->item_code ?? '',
                        'description' => $item->localizedDescription(),
                        'unit' => $item->unit ?? '',
                        'quantity' => $item->quantity ?? '',
                        'unit_value' => $item->unit_value ?? '',
                    ])->values()->all(),
                ],
            ])
            ->all();
    }
}
