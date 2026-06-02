<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label">{{ __('site.client_account') }}</label>
                <select name="client_user_id" class="form-input">
                    <option value="">{{ __('site.unassigned') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_user_id', $selectedClientId) === (string) $client->id)>{{ $client->name }} · {{ $client->email }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('site.proposal_signer') }}</label>
                <select name="signer_user_id" class="form-input">
                    <option value="">{{ __('site.unassigned') }}</option>
                    @foreach ($signers as $signer)
                        <option value="{{ $signer->id }}" @selected((string) old('signer_user_id', $selectedSignerId) === (string) $signer->id)>{{ $signer->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('site.form_status') }}</label>
                <select name="status" class="form-input" required>
                    @foreach (['draft', 'sent', 'approved', 'rejected'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $proposal->status) === $status)>{{ __("site.proposal_status_{$status}") }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('site.form_title') }}</label>
                <input name="title" value="{{ old('title', $proposal->title) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">{{ __('site.subject') }}</label>
                <input name="subject" value="{{ old('subject', $proposal->subject) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">{{ __('site.issued_at') }}</label>
                <input type="date" name="issued_at" value="{{ old('issued_at', optional($proposal->issued_at)->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('site.valid_until') }}</label>
                <input type="date" name="valid_until" value="{{ old('valid_until', optional($proposal->valid_until)->format('Y-m-d')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('site.validity_days') }}</label>
                <input type="number" step="1" min="1" max="365" name="validity_days" value="{{ old('validity_days', $proposal->validity_days ?? 30) }}" class="form-input" required>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">{{ __('site.proposal_description') }}</label>
                @php
                    $descriptionValue = old('description', $proposal->description ?: ($method === 'POST' ? __('site.proposal_description_template') : ''));
                @endphp
                <textarea name="description" rows="5" class="form-input" data-default-template="{{ e(__('site.proposal_description_template')) }}">{{ $descriptionValue }}</textarea>
                <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.proposal_description_template_help') }}</p>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">{{ __('site.proposal_scope') }}</label>
                <textarea name="scope" rows="4" class="form-input">{{ old('scope', $proposal->scope) }}</textarea>
            </div>
            <div>
                <label class="form-label">{{ __('site.timeline_months') }}</label>
                <input type="number" step="1" min="0" max="60" name="timeline_months" value="{{ old('timeline_months', $proposal->timeline_months ?? 1) }}" class="form-input" required>
            </div>
            <div>
                <label class="form-label">{{ __('site.timeline_weeks') }}</label>
                <input type="number" step="1" min="0" max="12" name="timeline_weeks" value="{{ old('timeline_weeks', $proposal->timeline_weeks ?? 0) }}" class="form-input" required>
            </div>
            <div class="md:col-span-2 rounded-2xl border border-olive-100 bg-olive-50 px-4 py-3 text-[15px] leading-6 text-olive-950">
                {{ __('site.proposal_timeline_help') }}
            </div>
            <div>
                <label class="form-label">{{ __('site.tax_rate') }}</label>
                <input type="number" step="0.1" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $proposal->tax_rate ?? 0) }}" class="form-input" required>
            </div>
        </div>
    </div>

    <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
        <h2 class="text-lg font-semibold text-stone-950">{{ __('site.proposal_payment_plan') }}</h2>
        <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.payment_schedule_help') }}</p>
        <div id="proposal-payments" class="mt-5 space-y-4">
            @foreach (old('payment_schedule', $paymentSchedule) as $index => $payment)
                <div data-existing-row="payment" class="proposal-payment-row grid gap-4 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_0.25fr_auto]">
                    <div>
                        <label class="form-label">{{ __('site.payment_label') }}</label>
                        <input name="payment_schedule[{{ $index }}][label]" value="{{ $payment['label'] ?? '' }}" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">{{ __('site.percentage') }}</label>
                        <input type="number" step="0.1" min="0.1" max="100" name="payment_schedule[{{ $index }}][percentage]" value="{{ $payment['percentage'] ?? '' }}" class="form-input" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="form-label">{{ __('site.payment_notes') }}</label>
                        <input name="payment_schedule[{{ $index }}][notes]" value="{{ $payment['notes'] ?? '' }}" class="form-input">
                    </div>
                    <div class="flex items-end">
                        <button type="button" data-remove-row="payment" data-confirm-message="{{ __('site.confirm_delete_payment_row_message') }}" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" id="add-proposal-payment" class="mt-4 rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_payment') }}</button>
    </div>

    <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
        <h2 class="text-lg font-semibold text-stone-950">{{ __('site.itemized_costs') }}</h2>
        <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.itemized_costs_help') }}</p>

        <div class="mt-5 rounded-2xl border border-dashed border-olive-200 bg-olive-50 p-4">
            <label class="form-label">{{ __('site.select_service_template') }}</label>
            <select
                class="form-input bg-white"
                data-proposal-template-select
                data-template-replace-message="{{ __('site.confirm_replace_cost_items_with_template') }}"
            >
                <option value="">{{ __('site.keep_current_cost_items') }}</option>
                @foreach ($proposalTemplates as $template)
                    <option value="{{ $template->id }}">
                        {{ str_pad((string) $template->service_number, 2, '0', STR_PAD_LEFT) }} · {{ $template->localizedName() }}
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-[15px] leading-6 text-olive-900">{{ __('site.service_template_help') }}</p>
        </div>

        <div class="mt-5 overflow-x-auto rounded-2xl border border-stone-200">
            <table class="min-w-[1120px] table-fixed text-left text-sm">
                <thead class="bg-stone-100 text-stone-600">
                    <tr>
                        <th class="w-24 px-4 py-3">{{ __('site.item_code') }}</th>
                        <th class="w-[28rem] px-4 py-3">{{ __('site.item_description') }}</th>
                        <th class="w-28 px-4 py-3">{{ __('site.unit_abbr') }}</th>
                        <th class="w-28 px-4 py-3">{{ __('site.qty_abbr') }}</th>
                        <th class="w-40 px-4 py-3">{{ __('site.unit_value_label') }}</th>
                        <th class="w-40 px-4 py-3 text-right">{{ __('site.total_value_label') }}</th>
                        <th class="w-28 px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody id="proposal-items" class="divide-y divide-stone-100 bg-white">
                    @foreach (old('items', $items) as $index => $item)
                        @php
                            $quantity = $item['quantity'] ?? '';
                            $unitValue = $item['unit_value'] ?? '';
                            $lineTotal = is_numeric($quantity) && is_numeric($unitValue) ? ((float) $quantity * (float) $unitValue) : 0;
                        @endphp
                        <tr data-existing-row="item" class="proposal-item-row">
                            <td class="px-4 py-3 align-top">
                                <input type="hidden" name="items[{{ $index }}][category]" value="{{ $item['category'] ?? '' }}">
                                <input name="items[{{ $index }}][item_code]" value="{{ $item['item_code'] ?? '' }}" class="form-input">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <textarea name="items[{{ $index }}][description]" rows="3" class="form-input" @required($index === 0)>{{ $item['description'] ?? '' }}</textarea>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}" class="form-input">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input data-cost-field="quantity" inputmode="numeric" name="items[{{ $index }}][quantity]" value="{{ $quantity }}" class="form-input">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input data-cost-field="unit_value" inputmode="decimal" name="items[{{ $index }}][unit_value]" value="{{ $unitValue }}" class="form-input">
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                <output data-line-total class="inline-flex min-h-11 w-full items-center justify-end rounded-xl bg-stone-50 px-3 font-semibold text-stone-900">{{ $lineTotal > 0 ? number_format($lineTotal, 2) : '—' }}</output>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <button type="button" data-remove-row="item" data-confirm-message="{{ __('site.confirm_delete_item_row_message') }}" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-stone-950 text-white">
                    <tr>
                        <td colspan="5" class="px-4 py-4 text-right text-sm font-semibold uppercase tracking-[0.14em]">{{ __('site.grand_total_value') }}</td>
                        <td class="px-4 py-4 text-right text-base font-semibold" data-items-grand-total>—</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <button type="button" id="add-proposal-item" class="mt-4 rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_item') }}</button>
    </div>

    <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
</form>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const itemsContainer = document.getElementById('proposal-items');
            const addItemButton = document.getElementById('add-proposal-item');
            const paymentsContainer = document.getElementById('proposal-payments');
            const addPaymentButton = document.getElementById('add-proposal-payment');
            const grandTotal = document.querySelector('[data-items-grand-total]');
            const description = document.querySelector('[name="description"][data-default-template]');
            const templateSelect = document.querySelector('[data-proposal-template-select]');
            const proposalTemplates = @json($proposalTemplatePayload ?? []);

            description?.addEventListener('focus', () => {
                if (description.value.trim() === '') {
                    description.value = description.dataset.defaultTemplate || '';
                    description.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }, { once: true });

            const parseMoney = (value) => {
                const normalized = String(value || '').replace(/[^0-9.,-]/g, '').replace(/,/g, '');
                const parsed = Number.parseFloat(normalized);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const formatMoney = (value) => value > 0 ? value.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }) : '—';

            const recalculateItems = () => {
                let total = 0;
                itemsContainer?.querySelectorAll('.proposal-item-row').forEach((row) => {
                    const quantity = parseMoney(row.querySelector('[data-cost-field="quantity"]')?.value);
                    const unitValue = parseMoney(row.querySelector('[data-cost-field="unit_value"]')?.value);
                    const lineTotal = quantity * unitValue;
                    total += lineTotal;
                    const output = row.querySelector('[data-line-total]');
                    if (output) output.textContent = formatMoney(lineTotal);
                });

                if (grandTotal) grandTotal.textContent = formatMoney(total);
            };

            const reindexRows = (container, rowSelector, prefix) => {
                container.querySelectorAll(rowSelector).forEach((row, index) => {
                    row.querySelectorAll('[name]').forEach((field) => {
                        field.name = field.name.replace(new RegExp(`${prefix}\\\\[\\\\d+\\\\]`), `${prefix}[${index}]`);
                    });
                    const descriptionField = row.querySelector('textarea[name*="[description]"]');
                    if (descriptionField) {
                        descriptionField.required = index === 0;
                    }
                });
            };

            const bindRemove = (button, container, rowSelector, prefix) => {
                button.addEventListener('click', () => {
                    if (container.querySelectorAll(rowSelector).length <= 1) return;
                    if (!window.confirm(button.dataset.confirmMessage)) return;
                    button.closest(rowSelector).remove();
                    reindexRows(container, rowSelector, prefix);
                    recalculateItems();
                });
            };

            const bindRemoveButtons = () => {
                document.querySelectorAll('[data-remove-row="item"]').forEach((button) => bindRemove(button, itemsContainer, '.proposal-item-row', 'items'));
                document.querySelectorAll('[data-remove-row="payment"]').forEach((button) => bindRemove(button, paymentsContainer, '.proposal-payment-row', 'payment_schedule'));
            };

            const itemRowHtml = (index, item = {}) => `
                <td class="px-4 py-3 align-top"><input type="hidden" name="items[${index}][category]" value="${escapeAttribute(item.category || '')}"><input name="items[${index}][item_code]" value="${escapeAttribute(item.item_code || '')}" class="form-input"></td>
                <td class="px-4 py-3 align-top"><textarea name="items[${index}][description]" rows="3" class="form-input" ${index === 0 ? 'required' : ''}>${escapeHtml(item.description || '')}</textarea></td>
                <td class="px-4 py-3 align-top"><input name="items[${index}][unit]" value="${escapeAttribute(item.unit || '')}" class="form-input"></td>
                <td class="px-4 py-3 align-top"><input data-cost-field="quantity" inputmode="numeric" name="items[${index}][quantity]" value="${escapeAttribute(item.quantity ?? '')}" class="form-input"></td>
                <td class="px-4 py-3 align-top"><input data-cost-field="unit_value" inputmode="decimal" name="items[${index}][unit_value]" value="${escapeAttribute(item.unit_value ?? '')}" class="form-input"></td>
                <td class="px-4 py-3 align-top text-right"><output data-line-total class="inline-flex min-h-11 w-full items-center justify-end rounded-xl bg-stone-50 px-3 font-semibold text-stone-900">—</output></td>
                <td class="px-4 py-3 align-top"><button type="button" data-remove-row="item" data-confirm-message="{{ __('site.confirm_delete_item_row_message') }}" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button></td>
            `;

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;');
            }

            function escapeAttribute(value) {
                return escapeHtml(value)
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            addPaymentButton?.addEventListener('click', () => {
                const index = paymentsContainer.querySelectorAll('.proposal-payment-row').length;
                const row = document.createElement('div');
                row.className = 'proposal-payment-row grid gap-4 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_0.25fr_auto]';
                row.innerHTML = `
                    <div><label class="form-label">{{ __('site.payment_label') }}</label><input name="payment_schedule[${index}][label]" class="form-input"></div>
                    <div><label class="form-label">{{ __('site.percentage') }}</label><input type="number" step="0.1" min="0.1" max="100" name="payment_schedule[${index}][percentage]" class="form-input" required></div>
                    <div class="md:col-span-2"><label class="form-label">{{ __('site.payment_notes') }}</label><input name="payment_schedule[${index}][notes]" class="form-input"></div>
                    <div class="flex items-end"><button type="button" data-remove-row="payment" data-confirm-message="{{ __('site.confirm_delete_payment_row_message') }}" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button></div>
                `;
                paymentsContainer.appendChild(row);
                bindRemove(row.querySelector('[data-remove-row="payment"]'), paymentsContainer, '.proposal-payment-row', 'payment_schedule');
            });

            addItemButton?.addEventListener('click', () => {
                const index = itemsContainer.querySelectorAll('.proposal-item-row').length;
                const row = document.createElement('tr');
                row.className = 'proposal-item-row';
                row.innerHTML = itemRowHtml(index);
                itemsContainer.appendChild(row);
                bindRemove(row.querySelector('[data-remove-row="item"]'), itemsContainer, '.proposal-item-row', 'items');
                row.querySelectorAll('[data-cost-field]').forEach((field) => field.addEventListener('input', recalculateItems));
            });

            templateSelect?.addEventListener('change', () => {
                const template = proposalTemplates[templateSelect.value];

                if (!template) return;

                if (!window.confirm(templateSelect.dataset.templateReplaceMessage)) {
                    templateSelect.value = '';
                    return;
                }

                itemsContainer.innerHTML = '';
                const rows = template.items?.length ? template.items : [{}, {}];

                rows.forEach((item, index) => {
                    const row = document.createElement('tr');
                    row.className = 'proposal-item-row';
                    row.innerHTML = itemRowHtml(index, item);
                    itemsContainer.appendChild(row);
                    bindRemove(row.querySelector('[data-remove-row="item"]'), itemsContainer, '.proposal-item-row', 'items');
                    row.querySelectorAll('[data-cost-field]').forEach((field) => field.addEventListener('input', recalculateItems));
                });

                recalculateItems();
            });

            bindRemoveButtons();
            itemsContainer?.querySelectorAll('[data-cost-field]').forEach((field) => field.addEventListener('input', recalculateItems));
            recalculateItems();
        });
    </script>
@endonce
