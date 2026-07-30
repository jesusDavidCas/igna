@extends('layouts.panel', ['title' => $proposal->proposal_number, 'heading' => $proposal->proposal_number])

@section('content')
    @php
        $whatsappClients = $clients->map(fn ($client): array => [
            'id' => $client->id,
            'name' => $client->name,
            'phone' => $client->phone,
        ])->values();
        $whatsappDefaultClient = $proposal->client || $proposal->prospect_name || $proposal->prospect_phone ? [
            'id' => $proposal->client?->id,
            'name' => $proposal->clientDisplayName(),
            'phone' => $proposal->clientDisplayPhone(),
        ] : null;
    @endphp

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between print:hidden">
        <div>
            <p class="text-[15px] text-stone-500">{{ $proposal->statusLabel() }}</p>
            <h2 class="text-2xl font-semibold text-stone-950">{{ $proposal->localizedTitle() }}</h2>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.proposals.pdf', $proposal) }}" target="_blank" rel="noopener" class="inline-flex items-center rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-olive-800">{{ __('site.generate_pdf') }}</a>
            <button type="button" data-toggle-whatsapp-panel class="inline-flex items-center rounded-full bg-stone-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-800">{{ __('site.create_whatsapp_link') }}</button>
            <a href="{{ route('admin.proposals.edit', $proposal) }}" class="inline-flex items-center rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-olive-600 hover:text-olive-800">{{ __('site.edit_proposal') }}</a>
        </div>
    </div>

    <section
        data-whatsapp-panel
        data-clients='@json($whatsappClients)'
        data-default-client='@json($whatsappDefaultClient)'
        data-default-country-code="57"
        data-message-template="{{ e(__('site.whatsapp_default_message', [
            'client' => '__CLIENT__',
            'proposal' => $proposal->proposal_number,
            'title' => $proposal->localizedTitle(),
            'link' => $proposalAccessUrl,
        ])) }}"
        class="mt-6 hidden rounded-[2rem] border border-olive-200 bg-olive-50 p-6 shadow-sm print:hidden"
    >
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-stone-950">{{ __('site.whatsapp_share_title') }}</h3>
                <p class="mt-2 max-w-3xl text-[15px] leading-6 text-stone-600">{{ __('site.whatsapp_share_intro') }}</p>
            </div>
            <a data-whatsapp-open href="#" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-olive-800">{{ __('site.open_whatsapp') }}</a>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
            <div class="grid gap-4 rounded-2xl bg-white p-4">
                <div>
                    <label class="form-label">{{ __('site.whatsapp_recipient') }}</label>
                    <select data-whatsapp-client class="form-input">
                        <option value="manual">{{ __('site.manual_recipient') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected($proposal->client_user_id === $client->id)>{{ $client->name }}{{ $client->phone ? ' · '.$client->phone : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">{{ __('site.recipient_name') }}</label>
                    <input data-whatsapp-name value="{{ $proposal->clientDisplayName() !== __('site.unassigned') ? $proposal->clientDisplayName() : '' }}" class="form-input">
                </div>
                <div class="grid gap-4 md:grid-cols-[0.35fr_0.65fr]">
                    <div>
                        <label class="form-label">{{ __('site.country_code') }}</label>
                        <input data-whatsapp-country-code value="57" inputmode="numeric" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.phone_number') }}</label>
                        <input data-whatsapp-phone value="{{ $proposal->clientDisplayPhone() }}" inputmode="tel" class="form-input">
                    </div>
                </div>
                <div>
                    <label class="form-label">{{ __('site.proposal_public_link') }}</label>
                    <input value="{{ $proposalAccessUrl }}" readonly class="form-input bg-stone-50 text-sm">
                </div>
                <p data-whatsapp-error class="hidden text-sm font-semibold text-rose-700">{{ __('site.whatsapp_phone_error') }}</p>
            </div>

            <div class="rounded-2xl bg-white p-4">
                <label class="form-label">{{ __('site.whatsapp_message') }}</label>
                <textarea data-whatsapp-message rows="8" class="form-input leading-6"></textarea>
                <div class="mt-4 rounded-2xl border border-stone-200 bg-gradient-to-br from-stone-50 to-olive-50/70 p-4">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">{{ __('site.whatsapp_final_preview') }}</p>
                    <div data-whatsapp-preview class="mt-3 whitespace-pre-line rounded-xl bg-white p-4 text-[15px] leading-7 text-stone-700 shadow-sm"></div>
                </div>
            </div>
        </div>
    </section>

    <article class="proposal-print mt-8 overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-sm print:mt-0 print:rounded-none print:border-0 print:shadow-none">
        <div class="border-b border-olive-100 p-6">
            @include('proposals.partials.header', ['headingTag' => 'h2'])
        </div>

        <div class="p-8">
        <div class="grid gap-6 md:grid-cols-2 print:grid-cols-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.client') }}</p>
                <p class="mt-2 text-stone-950">{{ $proposal->clientDisplayName() }}</p>
                <p class="text-[15px] text-stone-500">{{ $proposal->clientDisplayEmail() }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.subject') }}</p>
                <p class="mt-2 text-stone-950">{{ $proposal->subject }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.validity_days') }}</p>
                <p class="mt-2 text-stone-950">{{ $proposal->validityLabel() }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            @foreach (['description', 'scope'] as $field)
                <section class="rounded-2xl bg-stone-50 p-5">
                    <h3 class="font-semibold text-stone-950">{{ __("site.proposal_{$field}") }}</h3>
                    <div class="mt-3 rich-proposal-content text-base leading-7 text-stone-600 [&_li]:mb-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-3 [&_ul]:list-disc [&_ul]:pl-5">{!! app(\App\Support\Proposals\ProposalContentSanitizer::class)->clean($proposal->{$field}) ?: '—' !!}</div>
                </section>
            @endforeach
            <section class="rounded-2xl bg-stone-50 p-5">
                <h3 class="font-semibold text-stone-950">{{ __('site.proposal_timeline') }}</h3>
                <p class="mt-3 text-base leading-7 text-stone-600">{{ $proposal->formattedTimeline() }}</p>
            </section>
            <section class="rounded-2xl bg-stone-50 p-5">
                <h3 class="font-semibold text-stone-950">{{ __('site.proposal_payment_plan') }}</h3>
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
        </div>

        <div class="mt-8 overflow-x-auto rounded-2xl border border-stone-200">
            <table class="min-w-[920px] table-fixed text-left text-[15px] print:text-[11px]">
                <thead class="text-stone-500">
                    <tr>
                        <th class="w-24 bg-stone-50 px-4 py-3">{{ __('site.item_code') }}</th>
                        <th class="w-[34rem] bg-stone-50 px-4 py-3">{{ __('site.item_description') }}</th>
                        <th class="w-24 bg-stone-50 px-4 py-3">{{ __('site.unit_abbr') }}</th>
                        <th class="w-24 bg-stone-50 px-4 py-3 text-right">{{ __('site.qty_abbr') }}</th>
                        <th class="w-36 bg-stone-50 px-4 py-3 text-right">{{ __('site.unit_value_label') }}</th>
                        <th class="w-36 bg-stone-50 px-4 py-3 text-right">{{ __('site.total_value_label') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($proposal->items as $item)
                        <tr>
                            <td class="px-4 py-4 align-top">{{ $item->item_code ?: '—' }}</td>
                            <td class="px-4 py-4 align-top leading-7">{{ $item->description }}</td>
                            <td class="px-4 py-4 align-top">{{ $item->unit ?: '—' }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ (int) $item->quantity > 0 ? number_format((int) $item->quantity) : '—' }}</td>
                            <td class="px-4 py-4 text-right align-top">{{ (float) $item->unit_value > 0 ? number_format((float) $item->unit_value, 2) : '—' }}</td>
                            <td class="px-4 py-4 text-right align-top font-semibold">{{ (float) $item->subtotal > 0 ? number_format((float) $item->subtotal, 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-8">
            @include('proposals.partials.terms')
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_0.8fr]">
            <div class="rounded-2xl border border-stone-200 bg-stone-50 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.proposal_signer') }}</p>
                <div class="mt-5 min-h-24">
                    @if ($proposal->signer?->signatureUrl())
                        <img src="{{ $proposal->signer->signatureUrl() }}" alt="{{ __('site.signature_image') }}" class="h-20 max-w-xs object-contain">
                    @endif
                </div>
                <div class="border-t border-stone-300 pt-3">
                    <p class="font-semibold text-stone-950">{{ $proposal->signer?->name ?? __('site.unassigned') }}</p>
                    <p class="text-[15px] text-stone-500">{{ $proposal->signer?->role->label() }}</p>
                </div>
            </div>
            <div class="space-y-2 rounded-2xl bg-stone-950 p-5 text-[15px] text-white">
                <div class="flex justify-between"><span>{{ __('site.subtotal') }}</span><span>{{ number_format((float) $proposal->subtotal, 2) }}</span></div>
                <div class="flex justify-between"><span>{{ __('site.taxes') }} ({{ number_format((float) $proposal->tax_rate, 2) }}%)</span><span>{{ number_format((float) $proposal->tax_total, 2) }}</span></div>
                <div class="flex justify-between border-t border-white/10 pt-3 text-lg font-semibold"><span>{{ __('site.total') }}</span><span>{{ number_format((float) $proposal->total, 2) }}</span></div>
                <p class="border-t border-white/10 pt-3 text-[15px] text-stone-300">{{ $proposal->validityLabel() }}</p>
            </div>
        </div>
        </div>
    </article>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const panel = document.querySelector('[data-whatsapp-panel]');
            if (!panel) return;

            const toggle = document.querySelector('[data-toggle-whatsapp-panel]');
            const clientSelect = panel.querySelector('[data-whatsapp-client]');
            const nameInput = panel.querySelector('[data-whatsapp-name]');
            const countryCodeInput = panel.querySelector('[data-whatsapp-country-code]');
            const phoneInput = panel.querySelector('[data-whatsapp-phone]');
            const messageInput = panel.querySelector('[data-whatsapp-message]');
            const messagePreview = panel.querySelector('[data-whatsapp-preview]');
            const openLink = panel.querySelector('[data-whatsapp-open]');
            const error = panel.querySelector('[data-whatsapp-error]');
            const clients = JSON.parse(panel.dataset.clients || '[]');
            const defaultClient = JSON.parse(panel.dataset.defaultClient || 'null');
            const messageTemplate = panel.dataset.messageTemplate || '';

            const digits = (value) => String(value || '').replace(/\D/g, '');

            const splitPhone = (rawPhone) => {
                const normalized = digits(rawPhone);

                if (!normalized) {
                    return { countryCode: panel.dataset.defaultCountryCode || '57', phone: '' };
                }

                if (normalized.startsWith('57') && normalized.length > 10) {
                    return { countryCode: '57', phone: normalized.slice(2) };
                }

                if (String(rawPhone || '').trim().startsWith('+') && normalized.length > 10) {
                    return {
                        countryCode: normalized.slice(0, Math.max(1, normalized.length - 10)),
                        phone: normalized.slice(-10),
                    };
                }

                return { countryCode: panel.dataset.defaultCountryCode || '57', phone: normalized };
            };

            const selectedClient = () => clients.find((client) => String(client.id) === String(clientSelect.value));

            const escapeHtml = (value) => String(value || '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const renderPreview = () => {
                if (!messagePreview) return;

                const escaped = escapeHtml(messageInput.value);
                messagePreview.innerHTML = escaped.replace(/\*([^*\n]+)\*/g, '<strong class="font-semibold text-stone-950">$1</strong>');
            };

            const updateMessage = () => {
                const fallbackName = nameInput.value.trim() || '{{ __('site.manual_recipient') }}';
                messageInput.value = messageTemplate.replace('__CLIENT__', fallbackName);
                renderPreview();
                updateLink();
            };

            const updateLink = () => {
                const countryCode = digits(countryCodeInput.value);
                const phone = digits(phoneInput.value);
                const message = messageInput.value.trim();

                if (!countryCode || !phone || !message) {
                    openLink.href = '#';
                    openLink.classList.add('opacity-60');
                    return;
                }

                openLink.classList.remove('opacity-60');
                openLink.href = `https://wa.me/${countryCode}${phone}?text=${encodeURIComponent(message)}`;
            };

            const applyClient = (client) => {
                if (!client) return;

                nameInput.value = client.name || '';
                const phone = splitPhone(client.phone);
                countryCodeInput.value = phone.countryCode;
                phoneInput.value = phone.phone;
                updateMessage();
            };

            toggle?.addEventListener('click', () => {
                panel.classList.toggle('hidden');
                if (!panel.classList.contains('hidden')) {
                    messageInput.focus();
                }
            });

            clientSelect?.addEventListener('change', () => {
                if (clientSelect.value === 'manual') {
                    nameInput.value = '';
                    phoneInput.value = '';
                    countryCodeInput.value = panel.dataset.defaultCountryCode || '57';
                    updateMessage();
                    return;
                }

                applyClient(selectedClient());
            });

            [nameInput, countryCodeInput, phoneInput].forEach((field) => {
                field?.addEventListener('input', updateMessage);
            });

            messageInput?.addEventListener('input', updateLink);
            messageInput?.addEventListener('input', renderPreview);

            openLink?.addEventListener('click', (event) => {
                updateLink();

                if (openLink.getAttribute('href') === '#') {
                    event.preventDefault();
                    error.classList.remove('hidden');
                    return;
                }

                error.classList.add('hidden');
            });

            if (defaultClient) {
                applyClient(defaultClient);
            } else {
                updateMessage();
            }
        });
    </script>
@endsection
