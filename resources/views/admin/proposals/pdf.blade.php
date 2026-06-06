<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 7mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #ffffff;
            color: #1c1917;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.25px;
            line-height: 1.38;
        }
        body.density-spacious { font-size: 9.65px; }
        body.density-compact { font-size: 8.8px; line-height: 1.3; }
        .sheet {
            border: 1px solid #dce8d2;
            border-radius: 16px;
            padding: 8px;
            width: auto;
        }
        body.density-spacious .sheet,
        body.density-normal .sheet {
            min-height: 182mm;
        }
        .grid {
            border-collapse: separate;
            border-spacing: 6px;
            table-layout: fixed;
            width: 100%;
        }
        .card {
            background: #ffffff;
            border: 1px solid #e7e5e4;
            border-radius: 11px;
            padding: 8px 9px;
            vertical-align: top;
        }
        .soft {
            background: #fbfbf8;
        }
        .accent {
            background: #f5f8f1;
            border-color: #dce8d2;
        }
        .label {
            color: #52664a;
            font-size: 7.4px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .muted {
            color: #78716c;
        }
        .title {
            color: #11100f;
            font-size: 17.5px;
            line-height: 1.12;
            margin: 4px 0 3px;
        }
        .subtitle {
            color: #57534e;
            font-size: 9.5px;
            margin: 0;
        }
        .block-title {
            color: #1c1917;
            font-size: 10px;
            font-weight: 700;
            margin: 0 0 6px;
        }
        .tiny {
            font-size: 7.6px;
        }
        .qr {
            display: block;
            height: 66px;
            margin: 4px auto 2px;
            width: 66px;
        }
        .logo-row {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }
        .logo-cell {
            padding: 0 10px 0 0;
            text-align: center;
            vertical-align: middle;
            width: 30%;
        }
        .logo-img {
            display: block;
            height: auto;
            margin: 0 auto;
            max-height: 40px;
            max-width: 112px;
            object-fit: contain;
            width: auto;
        }
        .logo-mark {
            background: #52664a;
            border-radius: 999px;
            color: #ffffff;
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            height: 34px;
            line-height: 34px;
            text-align: center;
            width: 34px;
        }
        .meta-cell {
            vertical-align: middle;
            width: 70%;
        }
        .main-layout {
            border-collapse: separate;
            border-spacing: 6px;
            table-layout: fixed;
            width: 100%;
        }
        .summary-layout {
            border-collapse: separate;
            border-spacing: 6px;
            table-layout: fixed;
            width: 100%;
        }
        .summary-layout td {
            vertical-align: top;
        }
        .full-stack {
            border-spacing: 0 8px;
            width: 100%;
        }
        .long-items,
        .long-terms {
            margin-top: 8px;
        }
        .left-col {
            vertical-align: top;
            width: 36%;
        }
        .right-col {
            vertical-align: top;
            width: 64%;
        }
        .stack {
            border-collapse: separate;
            border-spacing: 0 6px;
            table-layout: fixed;
            width: 100%;
        }
        .copy {
            color: #57534e;
            line-height: 1.42;
            margin: 0;
        }
        .description-card,
        .scope-card,
        .timeline-card,
        .items-card,
        .terms-card { height: auto; }
        body.density-compact .sheet { padding: 7px; }
        body.density-compact .grid,
        body.density-compact .main-layout { border-spacing: 6px; }
        body.density-compact .stack { border-spacing: 0 6px; }
        body.density-compact .card { padding: 7px 8px; }
        .payment-row {
            border-bottom: 1px solid #ece7dc;
            padding: 2px 0;
        }
        .payment-row:last-child {
            border-bottom: 0;
        }
        .budget {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }
        .budget th {
            background: #f1f6ed;
            border-bottom: 1px solid #dce8d2;
            color: #3f4f39;
            font-size: 7.5px;
            letter-spacing: 0.5px;
            padding: 5px 5px;
            text-align: left;
            text-transform: uppercase;
        }
        .budget td {
            border-bottom: 1px solid #f1f1ef;
            font-size: 8.8px;
            line-height: 1.34;
            padding: 5px 5px;
            vertical-align: top;
        }
        body.density-spacious .budget td { font-size: 9.1px; padding: 6px 5px; }
        body.density-compact .budget th { font-size: 6.9px; padding: 4px 5px; }
        body.density-compact .budget td { font-size: 8px; line-height: 1.22; padding: 4px 5px; }
        .budget .num {
            text-align: right;
            white-space: nowrap;
        }
        .budget .item-code { width: 9%; }
        .budget .description { width: 43%; }
        .budget .unit { width: 8%; }
        .budget .qty { width: 8%; }
        .budget .money { width: 16%; }
        .category-row td {
            background: #fafaf7;
            color: #52664a;
            font-size: 7.8px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .terms ul {
            margin: 0;
            padding-left: 12px;
        }
        .terms li {
            color: #57534e;
            font-size: 8.2px;
            line-height: 1.28;
            margin: 0 0 2px;
        }
        body.density-spacious .terms li { font-size: 8.4px; line-height: 1.3; }
        body.density-compact .terms li { font-size: 7.2px; line-height: 1.2; margin-bottom: 1px; }
        .bottom {
            border-collapse: separate;
            border-spacing: 6px 0;
            table-layout: fixed;
            width: 100%;
        }
        body.density-spacious .bottom { margin-top: 18px; }
        body.density-normal .bottom { margin-top: 12px; }
        body.fill-bottom .bottom {
            margin-top: 12px;
        }
        .signature-cell {
            width: 58%;
        }
        .totals-cell {
            width: 42%;
        }
        .signature-img {
            display: block;
            max-height: 34px;
            max-width: 160px;
            object-fit: contain;
        }
        .signature-line {
            border-top: 1px solid #a8a29e;
            margin-top: 5px;
            padding-top: 4px;
        }
        .totals {
            background: #f5f8f1;
            border-color: #bfd4b0;
        }
        .total-row {
            border-bottom: 1px solid #dce8d2;
            display: table;
            font-size: 9.8px;
            padding: 2px 0;
            width: 100%;
        }
        .total-row span {
            display: table-cell;
        }
        .total-row span:last-child {
            text-align: right;
        }
        .grand {
            color: #2f3d2b;
            font-size: 13px;
            font-weight: 700;
        }
        .bottom .card { padding-bottom: 7px; padding-top: 7px; }
    </style>
</head>
@php
    $signatureDataUri = null;
    if ($proposal->signer?->signature_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($proposal->signer->signature_path)) {
        $signatureMime = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($proposal->signer->signature_path) ?: 'image/png';
        $signatureDataUri = 'data:'.$signatureMime.';base64,'.base64_encode(\Illuminate\Support\Facades\Storage::disk('public')->get($proposal->signer->signature_path));
    }

    $itemsByCategory = $proposal->items->groupBy(fn ($item) => $item->category ?: '');
    $itemCount = $proposal->items->count();
    $textVolume = mb_strlen((string) $proposal->description) + mb_strlen((string) $proposal->scope);
    $density = $itemCount <= 2 && $textVolume < 1200
        ? 'spacious'
        : ($itemCount > 4 || $textVolume > 1400 ? 'compact' : 'normal');
    $descriptionLimit = $density === 'compact' ? 430 : 620;
    $scopeLimit = $density === 'compact' ? 260 : 460;
    $itemDescriptionLimit = $density === 'compact' ? 112 : ($density === 'normal' ? 140 : 170);
    $terms = collect(__('site.proposal_terms_compact'));
    $usesLongFlow = $itemCount > 8 || $textVolume > 1800;
    $fillsBottom = $density === 'compact' && $itemCount >= 5 && $itemCount <= 6 && $textVolume < 1400;
@endphp
<body class="density-{{ $density }}{{ $fillsBottom ? ' fill-bottom' : '' }}">
    <div class="sheet">
        <table class="grid">
            <tr>
                <td class="card accent" style="width: 18%; text-align: center;">
                    <div class="label">{{ __('site.proposal_details') }}</div>
                    <div class="tiny muted" style="margin-top: 3px;">{{ __('site.scan_me') }}</div>
                    <img src="{{ $qrCodeDataUri }}" alt="QR" class="qr">
                    <div class="tiny muted">{{ __('site.view_proposal_online') }}</div>
                </td>
                <td class="card" style="width: 47%;">
                    <table class="logo-row">
                        <tr>
                            <td class="logo-cell">
                                @if (! empty($brand['logo_data_uri']))
                                    <img src="{{ $brand['logo_data_uri'] }}" alt="{{ $brand['company_name'] ?? 'IGNA Studio' }}" class="logo-img">
                                @else
                                    <span class="logo-mark">{{ $brand['logo_text'] ?? 'IG' }}</span>
                                @endif
                            </td>
                            <td class="meta-cell">
                                <div class="label">{{ __('site.quote_proposal') }}</div>
                                <h1 class="title">{{ \Illuminate\Support\Str::limit($proposal->title, 78) }}</h1>
                                <p class="subtitle">{{ \Illuminate\Support\Str::limit($proposal->subject, 108) }}</p>
                                <div class="tiny muted" style="margin-top: 5px;">
                                    <strong>{{ $proposal->proposal_number }}</strong>
                                    @if ($proposal->issued_at)
                                        · {{ $proposal->issued_at->format('Y-m-d') }}
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="card soft" style="width: 35%;">
                    <div class="label">{{ __('site.client_or_account') }}</div>
                    <div style="font-size: 10px; font-weight: 700; margin-top: 7px;">{{ \Illuminate\Support\Str::limit($proposal->clientDisplayName(), 70) }}</div>
                    @if ($proposal->clientDisplayEmail())
                        <div class="muted" style="margin-top: 3px;">{{ $proposal->clientDisplayEmail() }}</div>
                    @endif
                    @if ($proposal->clientDisplayPhone())
                        <div class="muted" style="margin-top: 2px;">{{ $proposal->clientDisplayPhone() }}</div>
                    @endif
                    <div class="tiny muted" style="margin-top: 7px;">{{ \Illuminate\Support\Str::limit($proposal->subject, 118) }}</div>
                </td>
            </tr>
        </table>

        @if ($usesLongFlow)
            <table class="summary-layout">
                <tr>
                    <td class="card" style="width: 42%;">
                        <h2 class="block-title">{{ __('site.description_detail') }}</h2>
                        <p class="copy">{{ \Illuminate\Support\Str::limit(trim((string) $proposal->description) ?: '—', $descriptionLimit) }}</p>
                    </td>
                    <td class="card" style="width: 30%;">
                        <h2 class="block-title">{{ __('site.scope_deliverables') }}</h2>
                        <p class="copy">{{ \Illuminate\Support\Str::limit(trim((string) $proposal->scope) ?: '—', $scopeLimit) }}</p>
                    </td>
                    <td class="card accent" style="width: 28%;">
                        <h2 class="block-title">{{ __('site.timeline_payment_plan') }}</h2>
                        <p class="copy"><strong>{{ $proposal->formattedTimeline() }}</strong></p>
                        <div style="margin-top: 5px;">
                            @foreach ($proposal->paymentScheduleRows() as $payment)
                                <div class="payment-row">
                                    <strong>{{ number_format((float) ($payment['percentage'] ?? 0), 1) }}%</strong>
                                    {{ \Illuminate\Support\Str::limit($payment['label'] ?? __('site.payment_installment'), 44) }}
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>

            <div class="card items-card long-items">
                <h2 class="block-title">{{ __('site.proposal_items') }}</h2>
                <table class="budget">
                    <thead>
                        <tr>
                            <th class="item-code">{{ __('site.item_code') }}</th>
                            <th class="description">{{ __('site.item_description') }}</th>
                            <th class="unit">{{ __('site.unit_abbr') }}</th>
                            <th class="qty num">{{ __('site.qty_abbr') }}</th>
                            <th class="money num">{{ __('site.unit_value_label') }}</th>
                            <th class="money num">{{ __('site.total_value_label') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($itemsByCategory as $category => $items)
                            @if ($category)
                                <tr class="category-row">
                                    <td colspan="6">{{ $category }}</td>
                                </tr>
                            @endif
                            @foreach ($items as $item)
                                <tr>
                                    <td>{{ $item->item_code ?: '—' }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->description, $itemDescriptionLimit) }}</td>
                                    <td>{{ $item->unit ?: '—' }}</td>
                                    <td class="num">{{ (int) $item->quantity > 0 ? number_format((int) $item->quantity) : '—' }}</td>
                                    <td class="num">{{ (float) $item->unit_value > 0 ? number_format((float) $item->unit_value, 2) : '—' }}</td>
                                    <td class="num"><strong>{{ (float) $item->subtotal > 0 ? number_format((float) $item->subtotal, 2) : '—' }}</strong></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card soft terms terms-card long-terms">
                <h2 class="block-title">{{ __('site.proposal_terms_title') }}</h2>
                <ul>
                    @foreach ($terms as $paragraph)
                        <li>{{ $paragraph }}</li>
                    @endforeach
                </ul>
            </div>
        @else
            <table class="main-layout">
                <tr>
                    <td class="left-col">
                        <table class="stack">
                            <tr>
                                <td class="card description-card">
                                    <h2 class="block-title">{{ __('site.description_detail') }}</h2>
                                    <p class="copy">{{ \Illuminate\Support\Str::limit(trim((string) $proposal->description) ?: '—', $descriptionLimit) }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="card scope-card">
                                    <h2 class="block-title">{{ __('site.scope_deliverables') }}</h2>
                                    <p class="copy">{{ \Illuminate\Support\Str::limit(trim((string) $proposal->scope) ?: '—', $scopeLimit) }}</p>
                                </td>
                            </tr>
                            <tr>
                                <td class="card accent timeline-card">
                                    <h2 class="block-title">{{ __('site.timeline_payment_plan') }}</h2>
                                    <p class="copy"><strong>{{ $proposal->formattedTimeline() }}</strong></p>
                                    <div style="margin-top: 5px;">
                                        @foreach ($proposal->paymentScheduleRows() as $payment)
                                            <div class="payment-row">
                                                <strong>{{ number_format((float) ($payment['percentage'] ?? 0), 1) }}%</strong>
                                                {{ \Illuminate\Support\Str::limit($payment['label'] ?? __('site.payment_installment'), 48) }}
                                                @if (! empty($payment['notes']))
                                                    <span class="muted">· {{ \Illuminate\Support\Str::limit($payment['notes'], 42) }}</span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="right-col">
                        <table class="stack">
                            <tr>
                                <td class="card items-card">
                                    <h2 class="block-title">{{ __('site.proposal_items') }}</h2>
                                    <table class="budget">
                                        <thead>
                                            <tr>
                                                <th class="item-code">{{ __('site.item_code') }}</th>
                                                <th class="description">{{ __('site.item_description') }}</th>
                                                <th class="unit">{{ __('site.unit_abbr') }}</th>
                                                <th class="qty num">{{ __('site.qty_abbr') }}</th>
                                                <th class="money num">{{ __('site.unit_value_label') }}</th>
                                                <th class="money num">{{ __('site.total_value_label') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($itemsByCategory as $category => $items)
                                                @if ($category)
                                                    <tr class="category-row">
                                                        <td colspan="6">{{ $category }}</td>
                                                    </tr>
                                                @endif
                                                @foreach ($items as $item)
                                                    <tr>
                                                        <td>{{ $item->item_code ?: '—' }}</td>
                                                        <td>{{ \Illuminate\Support\Str::limit($item->description, $itemDescriptionLimit) }}</td>
                                                        <td>{{ $item->unit ?: '—' }}</td>
                                                        <td class="num">{{ (int) $item->quantity > 0 ? number_format((int) $item->quantity) : '—' }}</td>
                                                        <td class="num">{{ (float) $item->unit_value > 0 ? number_format((float) $item->unit_value, 2) : '—' }}</td>
                                                        <td class="num"><strong>{{ (float) $item->subtotal > 0 ? number_format((float) $item->subtotal, 2) : '—' }}</strong></td>
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="card soft terms terms-card">
                                    <h2 class="block-title">{{ __('site.proposal_terms_title') }}</h2>
                                    <ul>
                                        @foreach ($terms as $paragraph)
                                            <li>{{ $paragraph }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        @endif

        <table class="bottom">
            <tr>
                <td class="card signature-cell">
                    <div class="label">{{ __('site.signature') }}</div>
                    @if ($signatureDataUri)
                        <img src="{{ $signatureDataUri }}" alt="{{ __('site.signature_image') }}" class="signature-img">
                    @endif
                    <div class="signature-line">
                        <strong>{{ $proposal->signer?->name ?? __('site.unassigned') }}</strong><br>
                        <span class="muted">{{ $proposal->signer?->role?->label() }}</span>
                    </div>
                </td>
                <td class="card totals totals-cell">
                    <div class="total-row"><span>{{ __('site.subtotal') }}</span><span>{{ number_format((float) $proposal->subtotal, 2) }}</span></div>
                    <div class="total-row"><span>{{ __('site.taxes') }} / IVA ({{ number_format((float) $proposal->tax_rate, 2) }}%)</span><span>{{ number_format((float) $proposal->tax_total, 2) }}</span></div>
                    <div class="total-row grand"><span>{{ __('site.total') }}</span><span>{{ number_format((float) $proposal->total, 2) }}</span></div>
                    <div class="tiny muted" style="margin-top: 6px;">
                        {{ $proposal->validityLabel() }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
