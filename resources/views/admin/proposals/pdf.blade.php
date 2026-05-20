<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #1c1917;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5px;
            line-height: 1.45;
        }
        .header {
            background: #1c1917;
            border-radius: 16px;
            color: #fff;
            padding: 22px 26px;
        }
        .brand-row {
            display: table;
            width: 100%;
        }
        .brand-main,
        .brand-side {
            display: table-cell;
            vertical-align: top;
        }
        .brand-side {
            text-align: right;
            width: 32%;
        }
        .eyebrow {
            color: #b8cba9;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }
        h1 {
            font-size: 21px;
            line-height: 1.15;
            margin: 10px 0 8px;
        }
        h2 {
            font-size: 12px;
            margin: 0 0 8px;
        }
        .subject {
            color: #d6d3d1;
            font-size: 10.5px;
            margin: 0;
        }
        .logo-mark {
            background: #52664a;
            border-radius: 999px;
            display: inline-block;
            font-weight: 700;
            height: 36px;
            line-height: 36px;
            margin-bottom: 8px;
            text-align: center;
            width: 36px;
        }
        .logo-img {
            border-radius: 999px;
            display: inline-block;
            height: 42px;
            margin-bottom: 8px;
            object-fit: cover;
            width: 42px;
        }
        .section {
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            margin-top: 12px;
            padding: 13px 15px;
        }
        .muted { color: #78716c; }
        .info-grid {
            display: table;
            margin-top: 12px;
            table-layout: fixed;
            width: 100%;
        }
        .info-cell {
            display: table-cell;
            padding-right: 10px;
            vertical-align: top;
            width: 33.33%;
        }
        .two-grid {
            display: table;
            margin-top: 12px;
            table-layout: fixed;
            width: 100%;
        }
        .two-cell {
            display: table-cell;
            padding-right: 10px;
            vertical-align: top;
            width: 50%;
        }
        .budget {
            border-collapse: collapse;
            margin-top: 14px;
            table-layout: fixed;
            width: 100%;
        }
        .budget th {
            border-bottom: 1px solid #d6d3d1;
            color: #57534e;
            font-size: 8.8px;
            padding: 7px 6px;
            text-align: left;
            text-transform: uppercase;
        }
        .budget td {
            border-bottom: 1px solid #f5f5f4;
            padding: 7px 6px;
            vertical-align: top;
        }
        .category-row td {
            background: #f2f6ef;
            color: #42543b;
            font-weight: 700;
        }
        .num {
            text-align: right;
            white-space: nowrap;
        }
        .code { width: 10%; }
        .description { width: 42%; }
        .unit { width: 9%; }
        .quantity { width: 9%; }
        .money { width: 15%; }
        .summary-grid {
            display: table;
            margin-top: 16px;
            table-layout: fixed;
            width: 100%;
        }
        .signature,
        .totals {
            display: table-cell;
            vertical-align: top;
        }
        .signature {
            padding-right: 18px;
            width: 55%;
        }
        .signature-box {
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            min-height: 122px;
            padding: 14px;
        }
        .signature-img {
            display: block;
            max-height: 58px;
            max-width: 210px;
            object-fit: contain;
        }
        .signature-line {
            border-top: 1px solid #a8a29e;
            margin-top: 22px;
            padding-top: 8px;
        }
        .totals {
            background: #1c1917;
            border-radius: 14px;
            color: #fff;
            padding: 14px;
            width: 45%;
        }
        .total-row {
            display: table;
            margin-bottom: 7px;
            width: 100%;
        }
        .total-row span {
            display: table-cell;
        }
        .total-row span:last-child {
            text-align: right;
        }
        .grand-total {
            border-top: 1px solid rgba(255, 255, 255, .25);
            font-size: 13px;
            font-weight: 700;
            padding-top: 9px;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand-row">
            <div class="brand-main">
                <div class="eyebrow">{{ __('site.quote_proposal') }}</div>
                <h1>{{ $proposal->title }}</h1>
                <p class="subject">{{ $proposal->subject }}</p>
            </div>
            <div class="brand-side">
                @if (! empty($brand['logo_data_uri']))
                    <img src="{{ $brand['logo_data_uri'] }}" alt="{{ $brand['company_name'] ?? 'IGNA Studio' }}" class="logo-img">
                @else
                    <div class="logo-mark">{{ $brand['logo_text'] ?? 'IG' }}</div>
                @endif
                <div class="eyebrow">{{ $brand['company_name'] ?? 'IGNA Studio' }}</div>
                <div>{{ $proposal->proposal_number }}</div>
                @if ($proposal->issued_at)
                    <div class="muted">{{ $proposal->issued_at->format('Y-m-d') }}</div>
                @endif
            </div>
        </div>
    </header>

    <section class="info-grid">
        <div class="info-cell section">
            <div class="eyebrow">{{ __('site.client_account') }}</div>
            <strong>{{ $proposal->client?->name ?? __('site.unassigned') }}</strong><br>
            <span class="muted">{{ $proposal->client?->email }}</span>
        </div>
        <div class="info-cell section">
            <div class="eyebrow">{{ __('site.subject') }}</div>
            {{ $proposal->subject }}
        </div>
        <div class="info-cell section">
            <div class="eyebrow">{{ __('site.validity_days') }}</div>
            {{ $proposal->validityLabel() }}<br>
            @if ($proposal->valid_until)
                <span class="muted">{{ __('site.valid_until') }}: {{ $proposal->valid_until->format('Y-m-d') }}</span>
            @endif
        </div>
    </section>

    <section class="two-grid">
        <div class="two-cell section">
            <h2>{{ __('site.proposal_description') }}</h2>
            {!! nl2br(e($proposal->description ?: '—')) !!}
        </div>
        <div class="two-cell section">
            <h2>{{ __('site.proposal_scope') }}</h2>
            {!! nl2br(e($proposal->scope ?: '—')) !!}
        </div>
    </section>

    <section class="two-grid">
        <div class="two-cell section">
            <h2>{{ __('site.proposal_timeline') }}</h2>
            {{ $proposal->formattedTimeline() }}
        </div>
        <div class="two-cell section">
            <h2>{{ __('site.proposal_payment_plan') }}</h2>
            @foreach ($proposal->paymentScheduleRows() as $payment)
                <div>
                    <strong>{{ number_format((float) ($payment['percentage'] ?? 0), 1) }}%</strong>
                    {{ $payment['label'] ?? __('site.payment_installment') }}
                    @if (! empty($payment['notes']))
                        <span class="muted"> · {{ $payment['notes'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <table class="budget">
        <thead>
            <tr>
                <th class="code">{{ __('site.item_code') }}</th>
                <th class="description">{{ __('site.item_description') }}</th>
                <th class="unit">{{ __('site.unit_abbr') }}</th>
                <th class="quantity num">{{ __('site.qty_abbr') }}</th>
                <th class="money num">{{ __('site.unit_value_label') }}</th>
                <th class="money num">{{ __('site.total_value_label') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($proposal->items as $item)
                <tr>
                    <td>{{ $item->item_code ?: '—' }}</td>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->unit ?: '—' }}</td>
                    <td class="num">{{ (int) $item->quantity > 0 ? number_format((int) $item->quantity) : '—' }}</td>
                    <td class="num">{{ (float) $item->unit_value > 0 ? number_format((float) $item->unit_value, 2) : '—' }}</td>
                    <td class="num">{{ (float) $item->subtotal > 0 ? number_format((float) $item->subtotal, 2) : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <section class="summary-grid">
        <div class="signature">
            <div class="signature-box">
                <div class="eyebrow">{{ __('site.proposal_signer') }}</div>
                @if ($proposal->signer?->signatureUrl())
                    <img src="{{ public_path(parse_url($proposal->signer->signatureUrl(), PHP_URL_PATH) ?: '') }}" alt="{{ __('site.signature_image') }}" class="signature-img">
                @endif
                <div class="signature-line">
                    <strong>{{ $proposal->signer?->name ?? __('site.unassigned') }}</strong><br>
                    <span class="muted">{{ $proposal->signer?->role->label() }}</span>
                </div>
            </div>
        </div>
        <div class="totals">
            <div class="total-row"><span>{{ __('site.subtotal') }}</span><span>{{ number_format((float) $proposal->subtotal, 2) }}</span></div>
            <div class="total-row"><span>{{ __('site.taxes') }} ({{ number_format((float) $proposal->tax_rate, 2) }}%)</span><span>{{ number_format((float) $proposal->tax_total, 2) }}</span></div>
            <div class="total-row grand-total"><span>{{ __('site.total') }}</span><span>{{ number_format((float) $proposal->total, 2) }}</span></div>
            <div style="border-top: 1px solid rgba(255,255,255,.2); margin-top: 10px; padding-top: 10px;">
                {{ $proposal->validityLabel() }}
                @if ($proposal->valid_until)
                    <br>{{ __('site.valid_until') }}: {{ $proposal->valid_until->format('Y-m-d') }}
                @endif
            </div>
        </div>
    </section>
</body>
</html>
