@extends('layouts.public', ['title' => $proposal->proposal_number])

@section('content')
    <article class="mx-auto max-w-6xl px-6 py-12 lg:px-8">
        <header class="overflow-hidden rounded-[2rem] bg-stone-950 p-8 text-white shadow-sm">
            <div class="flex flex-col gap-8 md:flex-row md:items-start md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-olive-200">{{ __('site.quote_proposal') }}</p>
                    <h1 class="mt-4 max-w-3xl text-3xl font-semibold md:text-4xl">{{ $proposal->title }}</h1>
                    <p class="mt-4 max-w-3xl text-base leading-7 text-stone-300">{{ $proposal->subject }}</p>
                </div>
                <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-3">
                    <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-olive-700 text-sm font-semibold">
                        @if (! empty($brand['logo_url']))
                            <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['company_name'] }}" class="h-full w-full object-cover">
                        @else
                            {{ $brand['logo_text'] }}
                        @endif
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.18em] text-olive-200">{{ $brand['company_name'] }}</p>
                        <p class="text-sm text-stone-300">{{ $proposal->proposal_number }}</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.client_account') }}</p>
                    <p class="mt-2 text-base text-stone-950">{{ $proposal->client?->name ?? __('site.unassigned') }}</p>
                    <p class="text-[15px] text-stone-500">{{ $proposal->client?->email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.proposal_timeline') }}</p>
                    <p class="mt-2 text-base text-stone-950">{{ $proposal->formattedTimeline() }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.validity_days') }}</p>
                    <p class="mt-2 text-base text-stone-950">{{ $proposal->validityLabel() }}</p>
                    @if ($proposal->valid_until)
                        <p class="text-[15px] text-stone-500">{{ __('site.valid_until') }}: {{ $proposal->valid_until->format('Y-m-d') }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-2">
                @foreach (['description', 'scope'] as $field)
                    <section class="rounded-2xl bg-stone-50 p-5">
                        <h2 class="font-semibold text-stone-950">{{ __("site.proposal_{$field}") }}</h2>
                        <p class="mt-3 whitespace-pre-line text-base leading-7 text-stone-600">{{ $proposal->{$field} ?: '—' }}</p>
                    </section>
                @endforeach
            </div>

            <div class="mt-8 overflow-x-auto">
                <table class="min-w-full text-left text-[15px]">
                    <thead class="text-stone-500">
                        <tr>
                            <th class="pb-3 pr-4">{{ __('site.item_code') }}</th>
                            <th class="pb-3 pr-4">{{ __('site.item_description') }}</th>
                            <th class="pb-3 pr-4">{{ __('site.unit_abbr') }}</th>
                            <th class="pb-3 pr-4">{{ __('site.qty_abbr') }}</th>
                            <th class="pb-3 pr-4">{{ __('site.unit_value_label') }}</th>
                            <th class="pb-3 text-right">{{ __('site.total_value_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($proposal->items as $item)
                            <tr>
                                <td class="py-3 pr-4">{{ $item->item_code ?: '—' }}</td>
                                <td class="py-3 pr-4">{{ $item->description }}</td>
                                <td class="py-3 pr-4">{{ $item->unit ?: '—' }}</td>
                                <td class="py-3 pr-4">{{ (int) $item->quantity > 0 ? number_format((int) $item->quantity) : '—' }}</td>
                                <td class="py-3 pr-4">{{ (float) $item->unit_value > 0 ? number_format((float) $item->unit_value, 2) : '—' }}</td>
                                <td class="py-3 text-right">{{ (float) $item->subtotal > 0 ? number_format((float) $item->subtotal, 2) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.75fr]">
                <section class="rounded-2xl bg-stone-50 p-5">
                    <h2 class="font-semibold text-stone-950">{{ __('site.proposal_payment_plan') }}</h2>
                    <div class="mt-3 space-y-2 text-base leading-7 text-stone-600">
                        @foreach ($proposal->paymentScheduleRows() as $payment)
                            <div class="grid grid-cols-[1fr_auto] gap-4">
                                <span>
                                    {{ $payment['label'] ?? __('site.payment_installment') }}
                                    @if (! empty($payment['notes']))
                                        <span class="block text-stone-500">{{ $payment['notes'] }}</span>
                                    @endif
                                </span>
                                <span class="font-semibold text-stone-900">{{ number_format((float) ($payment['percentage'] ?? 0), 1) }}%</span>
                            </div>
                        @endforeach
                    </div>
                </section>
                <aside class="space-y-2 rounded-2xl bg-stone-950 p-5 text-[15px] text-white">
                    <div class="flex justify-between"><span>{{ __('site.subtotal') }}</span><span>{{ number_format((float) $proposal->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('site.taxes') }} ({{ number_format((float) $proposal->tax_rate, 2) }}%)</span><span>{{ number_format((float) $proposal->tax_total, 2) }}</span></div>
                    <div class="flex justify-between border-t border-white/10 pt-3 text-lg font-semibold"><span>{{ __('site.total') }}</span><span>{{ number_format((float) $proposal->total, 2) }}</span></div>
                </aside>
            </div>

            <div class="mt-8 rounded-2xl border border-stone-200 bg-stone-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.proposal_signer') }}</p>
                <p class="mt-3 font-semibold text-stone-950">{{ $proposal->signer?->name ?? __('site.unassigned') }}</p>
                <p class="text-[15px] text-stone-500">{{ $proposal->signer?->role->label() }}</p>
            </div>
        </div>
    </article>
@endsection
