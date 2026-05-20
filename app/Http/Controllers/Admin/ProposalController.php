<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProposalRequest;
use App\Models\Proposal;
use App\Models\User;
use App\Support\Proposals\ProposalNumberGenerator;
use App\Support\Settings\BrandSettings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            'items' => $this->defaultItems(),
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
            $this->storeExcelFile($request, $proposal);

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
        ]);
    }

    public function update(ProposalRequest $request, Proposal $proposal): RedirectResponse
    {
        DB::transaction(function () use ($request, $proposal): void {
            $proposal->update($this->payload($request));
            $proposal->items()->delete();
            $this->syncItems($proposal, $request->validated('items'));
            $this->storeExcelFile($request, $proposal);
        });

        return redirect()->route('admin.proposals.show', $proposal)->with('success', __('site.proposal_updated'));
    }

    public function uploadExcel(Request $request, Proposal $proposal): RedirectResponse
    {
        $validated = $request->validate([
            'excel_file' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:10240'],
        ]);

        if ($proposal->source_excel_path) {
            Storage::disk('local')->delete($proposal->source_excel_path);
        }

        $file = $validated['excel_file'];

        $proposal->update([
            'source_excel_path' => $file->store("proposals/{$proposal->proposal_number}", 'local'),
            'source_excel_original_name' => $file->getClientOriginalName(),
        ]);

        // TODO: Parse the uploaded Excel file and map rows into proposal_items.
        return redirect()->route('admin.proposals.edit', $proposal)->with('success', __('site.proposal_excel_uploaded'));
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

    private function storeExcelFile(Request $request, Proposal $proposal): void
    {
        if (! $request->hasFile('source_excel_file')) {
            return;
        }

        if ($proposal->source_excel_path) {
            Storage::disk('local')->delete($proposal->source_excel_path);
        }

        $file = $request->file('source_excel_file');

        $proposal->forceFill([
            'source_excel_path' => $file->store("proposals/{$proposal->proposal_number}", 'local'),
            'source_excel_original_name' => $file->getClientOriginalName(),
        ])->save();

        // TODO: Parse this Excel file and map budget rows into proposal_items.
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

    private function defaultItems(): array
    {
        return [
            [
                'category' => '',
                'item_code' => '1',
                'description' => 'Levantamiento topográfico con RTK, con las especificaciones que exige el ministerio, incluye: instalación de mojones cada 500 m.',
                'unit' => 'km',
                'quantity' => '15',
                'unit_value' => '500000',
            ],
            [
                'category' => '',
                'item_code' => '2',
                'description' => 'Diseño hidráulico del proyecto de la red de acueducto, incluye planta de tratamiento de agua potable, red de aducción, conducción y distribución hacia los usuarios, cuadros de cálculo, modelo en EPANET y planos hidráulicos terminados.',
                'unit' => 'Und',
                'quantity' => '1',
                'unit_value' => '10000000',
            ],
            [
                'category' => '',
                'item_code' => '3',
                'description' => 'Diseño hidráulico de la planta de tratamiento de aguas residuales.',
                'unit' => 'Und',
                'quantity' => '1',
                'unit_value' => '8000000',
            ],
            [
                'category' => '',
                'item_code' => '4',
                'description' => 'Estudio hidrológico de la cuenca, incluye modelos en HEC-HMS y HEC-RAS.',
                'unit' => 'Und',
                'quantity' => '1',
                'unit_value' => '5000000',
            ],
            [
                'category' => '',
                'item_code' => __('site.calculated_item_code'),
                'description' => 'Cálculo y elaboración del presupuesto, análisis de precios unitarios, cantidades de obra, programación de obra, especificaciones técnicas, presupuesto de administración, imprevistos y utilidad, interventoría, factor multiplicador y cotizaciones.',
                'unit' => 'Und',
                'quantity' => '',
                'unit_value' => '',
            ],
            [
                'category' => '',
                'item_code' => __('site.optional_item_code'),
                'description' => 'Diseño hidráulico de alcantarillado sanitario',
                'unit' => '',
                'quantity' => '',
                'unit_value' => '',
            ],
            [
                'category' => '',
                'item_code' => __('site.optional_item_code'),
                'description' => 'Diseño hidráulico de alcantarillado pluvial',
                'unit' => '',
                'quantity' => '',
                'unit_value' => '',
            ],
        ];
    }
}
