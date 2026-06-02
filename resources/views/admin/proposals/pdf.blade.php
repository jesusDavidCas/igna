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
            background: #ffffff;
            border: 1px solid #e7e5e4;
            border-bottom: 2px solid #d8e6cf;
            border-radius: 18px;
            color: #1c1917;
            padding: 14px 16px;
        }
        .brand-row {
            display: table;
            table-layout: fixed;
            width: 100%;
        }
        .brand-logo,
        .brand-main,
        .brand-side {
            display: table-cell;
            vertical-align: middle;
        }
        .brand-logo {
            width: 18%;
        }
        .brand-main {
            padding: 0 16px;
            width: 56%;
        }
        .brand-side {
            text-align: right;
            width: 26%;
        }
        .eyebrow {
            color: #52664a;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
        }
        h1 {
            font-size: 19px;
            line-height: 1.15;
            margin: 7px 0 5px;
        }
        h2 {
            font-size: 12px;
            margin: 0 0 8px;
        }
        .subject {
            color: #57534e;
            font-size: 10.5px;
            margin: 0;
        }
        .logo-mark {
            background: #ffffff;
            border: 1px solid #d8e6cf;
            border-radius: 999px;
            color: #52664a;
            display: inline-block;
            font-weight: 700;
            height: 36px;
            line-height: 36px;
            text-align: center;
            width: 36px;
        }
        .logo-img {
            display: block;
            height: auto;
            max-height: 54px;
            max-width: 132px;
            width: auto;
        }
        .meta-box {
            background: #fafaf9;
            border: 1px solid #e7e5e4;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .meta-box strong {
            display: block;
            margin-top: 4px;
        }
        .soft-card {
            background: #fafaf9;
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            padding: 13px 15px;
        }
        .soft-card h2 {
            color: #1c1917;
        }
        .soft-card p {
            margin: 0;
        }
        .info-label {
            color: #78716c;
            font-size: 8.8px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
        }
        .info-value {
            color: #1c1917;
            margin-top: 6px;
        }
        .info-muted {
            color: #78716c;
            margin-top: 3px;
        }
        .rounded-table {
            border: 1px solid #e7e5e4;
            border-radius: 14px;
            margin-top: 14px;
            overflow: hidden;
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
            table-layout: fixed;
            width: 100%;
        }
        .budget th {
            background: #f4f7f1;
            border-bottom: 1px solid #d8e6cf;
            color: #3f4f39;
            font-size: 8.8px;
            padding: 8px 7px;
            text-align: left;
            text-transform: uppercase;
        }
        .budget td {
            border-bottom: 1px solid #f5f5f4;
            padding: 8px 7px;
            vertical-align: top;
        }
        .category-row td {
            background: #f4f7f1;
            color: #42543b;
            font-weight: 700;
        }
        .num {
            text-align: right;
            white-space: nowrap;
        }
        .code { width: 10%; }
        .description { width: 46%; }
        .unit { width: 9%; }
        .quantity { width: 9%; }
        .money { width: 13%; }
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
            background: #fafaf9;
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
            background: #fafaf9;
            border: 1px solid #d8e6cf;
            border-radius: 14px;
            color: #1c1917;
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
            border-top: 1px solid #b8cba9;
            font-size: 13px;
            font-weight: 700;
            padding-top: 9px;
        }
        .total-accent {
            color: #42543b;
            font-weight: 700;
        }
        .terms-section p {
            color: #57534e;
            margin: 0 0 7px;
        }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <header class="header">
        <div class="brand-row">
            <div class="brand-logo">
                @if (! empty($brand['logo_data_uri']))
                    <img src="{{ $brand['logo_data_uri'] }}" alt="{{ $brand['company_name'] ?? 'IGNA Studio' }}" class="logo-img">
                @else
                    <div class="logo-mark">{{ $brand['logo_text'] ?? 'IG' }}</div>
                @endif
            </div>
            <div class="brand-main">
                <div class="eyebrow">{{ __('site.quote_proposal') }}</div>
                <h1>{{ $proposal->title }}</h1>
                <p class="subject">{{ $proposal->subject }}</p>
            </div>
            <div class="brand-side">
                <div class="meta-box">
                    <div class="eyebrow">{{ $brand['company_name'] ?? 'IGNA Studio' }}</div>
                    <strong>{{ $proposal->proposal_number }}</strong>
                    @if ($proposal->issued_at)
                        <div class="muted">{{ $proposal->issued_at->format('Y-m-d') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </header>

    <section class="info-grid">
        <div class="info-cell">
            <div class="soft-card">
                <div class="info-label">{{ __('site.client_account') }}</div>
                <div class="info-value"><strong>{{ $proposal->client?->name ?? __('site.unassigned') }}</strong></div>
                <div class="info-muted">{{ $proposal->client?->email }}</div>
            </div>
        </div>
        <div class="info-cell">
            <div class="soft-card">
                <div class="info-label">{{ __('site.subject') }}</div>
                <div class="info-value">{{ $proposal->subject }}</div>
            </div>
        </div>
        <div class="info-cell">
            <div class="soft-card">
                <div class="info-label">{{ __('site.validity_days') }}</div>
                <div class="info-value">{{ $proposal->validityLabel() }}</div>
                @if ($proposal->valid_until)
                    <div class="info-muted">{{ __('site.valid_until') }}: {{ $proposal->valid_until->format('Y-m-d') }}</div>
                @endif
            </div>
        </div>
    </section>

    <section class="two-grid">
        <div class="two-cell">
            <div class="soft-card">
                <h2>{{ __('site.proposal_description') }}</h2>
                {!! nl2br(e($proposal->description ?: '—')) !!}
            </div>
        </div>
        <div class="two-cell">
            <div class="soft-card">
                <h2>{{ __('site.proposal_scope') }}</h2>
                {!! nl2br(e($proposal->scope ?: '—')) !!}
            </div>
        </div>
    </section>

    <section class="two-grid">
        <div class="two-cell">
            <div class="soft-card">
                <h2>{{ __('site.proposal_timeline') }}</h2>
                {{ $proposal->formattedTimeline() }}
            </div>
        </div>
        <div class="two-cell">
            <div class="soft-card">
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
        </div>
    </section>

    <div class="rounded-table">
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
    </div>

    @include('proposals.partials.terms-pdf')

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
            <div class="total-row grand-total total-accent"><span>{{ __('site.total') }}</span><span>{{ number_format((float) $proposal->total, 2) }}</span></div>
            <div style="border-top: 1px solid #b8cba9; margin-top: 10px; padding-top: 10px;">
                {{ $proposal->validityLabel() }}
                @if ($proposal->valid_until)
                    <br>{{ __('site.valid_until') }}: {{ $proposal->valid_until->format('Y-m-d') }}
                @endif
            </div>
        </div>
    </section>
</body>
</html>
