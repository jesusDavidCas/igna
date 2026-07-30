@php
    $fieldId = fn (string $name): string => 'field-'.str_replace(['.', '[', ']'], '-', $name);
    $errorId = fn (string $name): string => 'error-'.str_replace(['.', '[', ']'], '-', $name);
    $fieldClass = fn (string $name): string => 'form-input'.($errors->has($name) ? ' border-rose-400 ring-1 ring-rose-200 focus:border-rose-500 focus:ring-rose-200' : '');
    $errorAttributes = fn (string $name): string => $errors->has($name) ? 'aria-invalid="true" aria-describedby="'.$errorId($name).'"' : '';
    $firstErrorField = array_key_first($errors->messages() ?: []);
    $sectionClass = 'rounded-[1.5rem] border border-stone-200 bg-white p-6 shadow-sm md:p-8';
    $sectionHeadingClass = 'text-lg font-semibold text-stone-950';
    $sectionCopyClass = 'mt-2 max-w-3xl text-[15px] leading-6 text-stone-500';
    $contentLocale = app()->getLocale() === 'es' ? 'es' : 'en';
    $targetLocale = $contentLocale === 'es' ? 'en' : 'es';
@endphp

@if ($errors->any())
    <section
        id="proposal-validation-summary"
        data-validation-summary
        data-first-error-target="{{ $firstErrorField ? $fieldId($firstErrorField) : '' }}"
        tabindex="-1"
        class="mb-6 rounded-[1.5rem] border border-rose-200 bg-rose-50 p-5 text-rose-950 shadow-sm"
    >
        <h2 class="text-base font-semibold">{{ __('site.validation_summary') }}</h2>
        <p class="mt-2 text-sm">{{ trans_choice('site.validation_summary_count', $errors->count(), ['count' => $errors->count()]) }}</p>
        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm">
            @foreach ($errors->messages() as $field => $messages)
                <li>
                    <a href="#{{ $fieldId($field) }}" class="font-semibold underline decoration-rose-300 underline-offset-4">
                        {{ $messages[0] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endif

<form id="proposal-form" method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="content_locale" value="{{ $contentLocale }}">

    <section class="{{ $sectionClass }}" data-proposal-section="identity">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">01</p>
            <h2 class="{{ $sectionHeadingClass }}">{{ __('site.proposal_information') }}</h2>
            <p class="{{ $sectionCopyClass }}">{{ __('site.proposal_information_help') }}</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            @if ($proposal->exists)
                <div>
                    <label for="proposal-number-display" class="form-label">{{ __('site.proposal_number') }}</label>
                    <input id="proposal-number-display" value="{{ $proposal->proposal_number }}" class="form-input bg-stone-50" readonly>
                </div>
            @endif
            <div>
                <label for="{{ $fieldId('status') }}" class="form-label">{{ __('site.form_status') }}</label>
                <select id="{{ $fieldId('status') }}" name="status" class="{{ $fieldClass('status') }}" required {!! $errorAttributes('status') !!}>
                    @foreach (['draft', 'sent', 'approved', 'rejected'] as $statusOption)
                        <option value="{{ $statusOption }}" @selected(old('status', $proposal->status) === $statusOption)>{{ __("site.proposal_status_{$statusOption}") }}</option>
                    @endforeach
                </select>
                @error('status') <p id="{{ $errorId('status') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('title') }}" class="form-label">{{ __('site.form_title') }}</label>
                <input id="{{ $fieldId('title') }}" name="title" value="{{ old('title', old("title_{$contentLocale}", $contentLocale === 'es' ? ($proposal->title_es ?: $proposal->localizedTitle()) : ($proposal->title_en ?: $proposal->title))) }}" class="{{ $fieldClass("title_{$contentLocale}") }}" required {!! $errorAttributes("title_{$contentLocale}") !!}>
                <input type="hidden" name="title_{{ $targetLocale }}" value="{{ old("title_{$targetLocale}", $targetLocale === 'es' ? $proposal->title_es : $proposal->title_en) }}">
                @error("title_{$contentLocale}") <p id="{{ $errorId("title_{$contentLocale}") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('subject') }}" class="form-label">{{ __('site.subject') }}</label>
                <input id="{{ $fieldId('subject') }}" name="subject" value="{{ old('subject', $proposal->subject) }}" class="{{ $fieldClass('subject') }}" required {!! $errorAttributes('subject') !!}>
                @error('subject') <p id="{{ $errorId('subject') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('issued_at') }}" class="form-label">{{ __('site.issued_at') }}</label>
                <input id="{{ $fieldId('issued_at') }}" type="date" name="issued_at" value="{{ old('issued_at', optional($proposal->issued_at)->format('Y-m-d')) }}" class="{{ $fieldClass('issued_at') }}" {!! $errorAttributes('issued_at') !!}>
                @error('issued_at') <p id="{{ $errorId('issued_at') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('valid_until') }}" class="form-label">{{ __('site.valid_until') }}</label>
                <input id="{{ $fieldId('valid_until') }}" type="date" name="valid_until" value="{{ old('valid_until', optional($proposal->valid_until)->format('Y-m-d')) }}" class="{{ $fieldClass('valid_until') }}" {!! $errorAttributes('valid_until') !!}>
                @error('valid_until') <p id="{{ $errorId('valid_until') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="{{ $sectionClass }}" data-proposal-section="client">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">02</p>
            <h2 class="{{ $sectionHeadingClass }}">{{ __('site.client_information') }}</h2>
            <p class="{{ $sectionCopyClass }}">{{ __('site.client_information_help') }}</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="{{ $fieldId('client_user_id') }}" class="form-label">{{ __('site.select_existing_client') }}</label>
                <select id="{{ $fieldId('client_user_id') }}" name="client_user_id" class="{{ $fieldClass('client_user_id') }}" {!! $errorAttributes('client_user_id') !!}>
                    <option value="">{{ __('site.enter_client_information_manually') }}</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) old('client_user_id', $selectedClientId) === (string) $client->id)>{{ $client->name }} · {{ $client->email }}</option>
                    @endforeach
                </select>
                @error('client_user_id') <p id="{{ $errorId('client_user_id') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="rounded-2xl border border-olive-100 bg-olive-50 px-4 py-3 text-[15px] leading-6 text-olive-950">
                {{ __('site.manual_client_fields_help') }}
            </div>
            <div>
                <label for="{{ $fieldId('prospect_name') }}" class="form-label">{{ __('site.manual_client_name') }}</label>
                <input id="{{ $fieldId('prospect_name') }}" name="prospect_name" value="{{ old('prospect_name', $proposal->prospect_name) }}" class="{{ $fieldClass('prospect_name') }}" {!! $errorAttributes('prospect_name') !!}>
                @error('prospect_name') <p id="{{ $errorId('prospect_name') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('prospect_email') }}" class="form-label">{{ __('site.manual_client_email') }}</label>
                <input id="{{ $fieldId('prospect_email') }}" type="email" name="prospect_email" value="{{ old('prospect_email', $proposal->prospect_email) }}" class="{{ $fieldClass('prospect_email') }}" {!! $errorAttributes('prospect_email') !!}>
                @error('prospect_email') <p id="{{ $errorId('prospect_email') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('prospect_phone') }}" class="form-label">{{ __('site.manual_client_phone') }}</label>
                <input id="{{ $fieldId('prospect_phone') }}" name="prospect_phone" value="{{ old('prospect_phone', $proposal->prospect_phone) }}" class="{{ $fieldClass('prospect_phone') }}" {!! $errorAttributes('prospect_phone') !!}>
                @error('prospect_phone') <p id="{{ $errorId('prospect_phone') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section class="{{ $sectionClass }}" data-proposal-section="scope">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">03</p>
            <h2 class="{{ $sectionHeadingClass }}">{{ __('site.scope_and_deliverables') }}</h2>
            <p class="{{ $sectionCopyClass }}">{{ __('site.scope_and_deliverables_help') }}</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                @php
                    $descriptionValue = old('description', $proposal->description ?: ($method === 'POST' ? __('site.proposal_description_template') : ''));
                @endphp
                @include('admin.proposals.partials.rich-text-field', [
                    'id' => $fieldId('description'),
                    'name' => 'description',
                    'label' => __('site.proposal_description'),
                    'value' => $descriptionValue,
                    'help' => __('site.proposal_description_template_help'),
                    'warningThreshold' => 1400,
                ])
            </div>
            <div class="md:col-span-2">
                @include('admin.proposals.partials.rich-text-field', [
                    'id' => $fieldId('scope'),
                    'name' => 'scope',
                    'label' => __('site.proposal_scope'),
                    'value' => old('scope', $proposal->scope),
                    'help' => null,
                    'warningThreshold' => 1000,
                ])
            </div>
            <div>
                <label for="{{ $fieldId('timeline_months') }}" class="form-label">{{ __('site.timeline_months') }}</label>
                <input id="{{ $fieldId('timeline_months') }}" type="number" step="1" min="0" max="60" name="timeline_months" value="{{ old('timeline_months', $proposal->timeline_months ?? 1) }}" class="{{ $fieldClass('timeline_months') }}" required {!! $errorAttributes('timeline_months') !!}>
                @error('timeline_months') <p id="{{ $errorId('timeline_months') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="{{ $fieldId('timeline_weeks') }}" class="form-label">{{ __('site.timeline_weeks') }}</label>
                <input id="{{ $fieldId('timeline_weeks') }}" type="number" step="1" min="0" max="12" name="timeline_weeks" value="{{ old('timeline_weeks', $proposal->timeline_weeks ?? 0) }}" class="{{ $fieldClass('timeline_weeks') }}" required {!! $errorAttributes('timeline_weeks') !!}>
                @error('timeline_weeks') <p id="{{ $errorId('timeline_weeks') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 rounded-2xl border border-olive-100 bg-olive-50 px-4 py-3 text-[15px] leading-6 text-olive-950">
                {{ __('site.proposal_timeline_help') }}
            </div>
        </div>
    </section>

    <section class="{{ $sectionClass }}" data-proposal-section="payments">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">04</p>
            <h2 class="{{ $sectionHeadingClass }}">{{ __('site.payment_schedule_and_totals') }}</h2>
            <p class="{{ $sectionCopyClass }}">{{ __('site.payment_schedule_help') }}</p>
        </div>

        <div id="proposal-payments" class="mt-5 space-y-4">
            @foreach (old('payment_schedule', $paymentSchedule) as $index => $payment)
                <div data-existing-row="payment" class="proposal-payment-row grid gap-4 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_0.25fr_auto]">
                    <div>
                        <label for="{{ $fieldId("payment_schedule.$index.label") }}" class="form-label">{{ __('site.payment_label') }}</label>
                        <input id="{{ $fieldId("payment_schedule.$index.label") }}" name="payment_schedule[{{ $index }}][label]" value="{{ $payment['label'] ?? '' }}" class="{{ $fieldClass("payment_schedule.$index.label") }}" {!! $errorAttributes("payment_schedule.$index.label") !!}>
                        @error("payment_schedule.$index.label") <p id="{{ $errorId("payment_schedule.$index.label") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="{{ $fieldId("payment_schedule.$index.percentage") }}" class="form-label">{{ __('site.percentage') }}</label>
                        <input id="{{ $fieldId("payment_schedule.$index.percentage") }}" type="number" step="0.1" min="0.1" max="100" name="payment_schedule[{{ $index }}][percentage]" value="{{ $payment['percentage'] ?? '' }}" class="{{ $fieldClass("payment_schedule.$index.percentage") }}" required {!! $errorAttributes("payment_schedule.$index.percentage") !!}>
                        @error("payment_schedule.$index.percentage") <p id="{{ $errorId("payment_schedule.$index.percentage") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label for="{{ $fieldId("payment_schedule.$index.notes") }}" class="form-label">{{ __('site.payment_notes') }}</label>
                        <input id="{{ $fieldId("payment_schedule.$index.notes") }}" name="payment_schedule[{{ $index }}][notes]" value="{{ $payment['notes'] ?? '' }}" class="{{ $fieldClass("payment_schedule.$index.notes") }}" {!! $errorAttributes("payment_schedule.$index.notes") !!}>
                        @error("payment_schedule.$index.notes") <p id="{{ $errorId("payment_schedule.$index.notes") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="button" data-remove-row="payment" data-confirm-message="{{ __('site.confirm_delete_payment_row_message') }}" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
        @error('payment_schedule') <p id="{{ $errorId('payment_schedule') }}" class="mt-3 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
        <button type="button" id="add-proposal-payment" class="mt-4 rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_payment') }}</button>

        <div class="mt-6 grid gap-5 md:grid-cols-[0.4fr_0.6fr]">
            <div>
                <label for="{{ $fieldId('tax_rate') }}" class="form-label">{{ __('site.tax_rate') }}</label>
                <input id="{{ $fieldId('tax_rate') }}" type="number" step="0.1" min="0" max="100" name="tax_rate" value="{{ old('tax_rate', $proposal->tax_rate ?? 0) }}" class="{{ $fieldClass('tax_rate') }}" required {!! $errorAttributes('tax_rate') !!}>
                @error('tax_rate') <p id="{{ $errorId('tax_rate') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="rounded-2xl bg-stone-950 p-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-300">{{ __('site.grand_total_value') }}</p>
                <p class="mt-2 text-2xl font-semibold" data-items-grand-total>—</p>
            </div>
        </div>
    </section>

    <section class="{{ $sectionClass }}" data-proposal-section="costs">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">05</p>
            <h2 class="{{ $sectionHeadingClass }}">{{ __('site.cost_items') }}</h2>
            <p class="{{ $sectionCopyClass }}">{{ __('site.itemized_costs_help') }}</p>
        </div>

        <div class="mt-5 rounded-2xl border border-dashed border-olive-200 bg-olive-50 p-4">
            <div class="grid gap-4 lg:grid-cols-[1fr_0.28fr_auto] lg:items-end">
                <div>
                    <label for="proposal-template-select" class="form-label">{{ __('site.select_proposal_template') }}</label>
                    <select
                        id="proposal-template-select"
                        class="form-input bg-white"
                        data-proposal-template-select
                        data-template-duplicate-message="{{ __('site.template_already_present_confirm') }}"
                    >
                        <option value="">{{ __('site.select_proposal_template') }}</option>
                        @foreach ($proposalTemplates as $template)
                            <option value="{{ $template->id }}">
                                {{ str_pad((string) $template->service_number, 2, '0', STR_PAD_LEFT) }} · {{ $template->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="proposal-template-copies" class="form-label">{{ __('site.template_copies') }}</label>
                    <input id="proposal-template-copies" data-proposal-template-copies type="number" min="1" max="20" step="1" value="1" class="form-input bg-white">
                </div>
                <button type="button" data-add-template-items class="rounded-full bg-olive-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">{{ __('site.add_template_items') }}</button>
            </div>
            <div class="mt-3 flex flex-col gap-2 text-[15px] leading-6 text-olive-900 sm:flex-row sm:items-center sm:justify-between">
                <p>{{ __('site.proposal_template_append_help') }}</p>
                <a href="{{ route('admin.proposal-templates.index') }}" class="font-semibold text-olive-800 underline decoration-olive-300 underline-offset-4">{{ __('site.manage_proposal_templates') }}</a>
            </div>
            <p data-template-added-message class="mt-2 hidden text-sm font-semibold text-olive-950">{{ __('site.template_items_added') }}</p>
        </div>

        <div id="proposal-items" class="mt-5 space-y-4">
            @foreach (old('items', $items) as $index => $item)
                @php
                    $quantity = $item['quantity'] ?? '';
                    $unitValue = $item['unit_value'] ?? '';
                    $lineTotal = is_numeric($quantity) && is_numeric($unitValue) ? ((float) $quantity * (float) $unitValue) : 0;
                @endphp
                <div data-existing-row="item" class="proposal-item-row rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="grid gap-4 lg:grid-cols-[0.6fr_1.4fr_0.5fr_0.45fr_0.7fr_0.7fr_auto]">
                        <div>
                            <label for="{{ $fieldId("items.$index.item_code") }}" class="form-label">{{ __('site.item_code') }}</label>
                            <input type="hidden" name="items[{{ $index }}][category]" value="{{ $item['category'] ?? '' }}">
                            <input id="{{ $fieldId("items.$index.item_code") }}" name="items[{{ $index }}][item_code]" value="{{ $item['item_code'] ?? '' }}" class="{{ $fieldClass("items.$index.item_code") }}" {!! $errorAttributes("items.$index.item_code") !!}>
                            @error("items.$index.item_code") <p id="{{ $errorId("items.$index.item_code") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="{{ $fieldId("items.$index.description") }}" class="form-label">{{ __('site.item_description') }}</label>
                            <textarea id="{{ $fieldId("items.$index.description") }}" name="items[{{ $index }}][description]" rows="3" class="{{ $fieldClass("items.$index.description") }}" @required($index === 0) {!! $errorAttributes("items.$index.description") !!}>{{ $item['description'] ?? '' }}</textarea>
                            @error("items.$index.description") <p id="{{ $errorId("items.$index.description") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="{{ $fieldId("items.$index.unit") }}" class="form-label">{{ __('site.unit_abbr') }}</label>
                            <input id="{{ $fieldId("items.$index.unit") }}" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}" class="{{ $fieldClass("items.$index.unit") }}" {!! $errorAttributes("items.$index.unit") !!}>
                            @error("items.$index.unit") <p id="{{ $errorId("items.$index.unit") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="{{ $fieldId("items.$index.quantity") }}" class="form-label">{{ __('site.qty_abbr') }}</label>
                            <input id="{{ $fieldId("items.$index.quantity") }}" data-cost-field="quantity" inputmode="numeric" name="items[{{ $index }}][quantity]" value="{{ $quantity }}" class="{{ $fieldClass("items.$index.quantity") }}" {!! $errorAttributes("items.$index.quantity") !!}>
                            @error("items.$index.quantity") <p id="{{ $errorId("items.$index.quantity") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="{{ $fieldId("items.$index.unit_value") }}" class="form-label">{{ __('site.unit_value_label') }}</label>
                            <input id="{{ $fieldId("items.$index.unit_value") }}" data-cost-field="unit_value" inputmode="decimal" name="items[{{ $index }}][unit_value]" value="{{ $unitValue }}" class="{{ $fieldClass("items.$index.unit_value") }}" {!! $errorAttributes("items.$index.unit_value") !!}>
                            @error("items.$index.unit_value") <p id="{{ $errorId("items.$index.unit_value") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.total_value_label') }}</label>
                            <output data-line-total class="inline-flex min-h-11 w-full items-center justify-end rounded-xl bg-white px-3 font-semibold text-stone-900">{{ $lineTotal > 0 ? number_format($lineTotal, 2) : '—' }}</output>
                        </div>
                        <div class="flex items-end">
                            <button type="button" data-remove-row="item" data-confirm-message="{{ __('site.confirm_delete_item_row_message') }}" class="w-full rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @error('items') <p id="{{ $errorId('items') }}" class="mt-3 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
        <button type="button" id="add-proposal-item" class="mt-4 rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_item') }}</button>
    </section>

    <section class="{{ $sectionClass }}" data-proposal-section="publication">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">06</p>
            <h2 class="{{ $sectionHeadingClass }}">{{ __('site.signer_and_publication') }}</h2>
            <p class="{{ $sectionCopyClass }}">{{ __('site.signer_and_publication_help') }}</p>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-[1fr_auto] md:items-end">
            <div>
                <label for="{{ $fieldId('signer_user_id') }}" class="form-label">{{ __('site.proposal_signer') }}</label>
                <select id="{{ $fieldId('signer_user_id') }}" name="signer_user_id" class="{{ $fieldClass('signer_user_id') }}" {!! $errorAttributes('signer_user_id') !!}>
                    <option value="">{{ __('site.unassigned') }}</option>
                    @foreach ($signers as $signer)
                        <option value="{{ $signer->id }}" @selected((string) old('signer_user_id', $selectedSignerId) === (string) $signer->id)>{{ $signer->name }}</option>
                    @endforeach
                </select>
                @error('signer_user_id') <p id="{{ $errorId('signer_user_id') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">{{ __('site.save_changes') }}</button>
        </div>
    </section>
</form>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const summary = document.querySelector('[data-validation-summary]');
            const firstErrorTarget = summary?.dataset.firstErrorTarget ? document.getElementById(summary.dataset.firstErrorTarget) : null;
            const visibleFirstErrorTarget = firstErrorTarget?.matches('[data-rich-text-input]')
                ? document.querySelector(`[data-rich-text-editor][data-rich-text-target="${firstErrorTarget.id}"]`)
                : firstErrorTarget;
            if (summary && visibleFirstErrorTarget) {
                summary.focus({ preventScroll: true });
                visibleFirstErrorTarget.scrollIntoView({ block: 'center', behavior: 'smooth' });
                if (typeof visibleFirstErrorTarget.focus === 'function') visibleFirstErrorTarget.focus({ preventScroll: true });
            }

            const itemsContainer = document.getElementById('proposal-items');
            const addItemButton = document.getElementById('add-proposal-item');
            const paymentsContainer = document.getElementById('proposal-payments');
            const addPaymentButton = document.getElementById('add-proposal-payment');
            const grandTotal = document.querySelector('[data-items-grand-total]');
            const templateSelect = document.querySelector('[data-proposal-template-select]');
            const templateCopies = document.querySelector('[data-proposal-template-copies]');
            const addTemplateButton = document.querySelector('[data-add-template-items]');
            const templateAddedMessage = document.querySelector('[data-template-added-message]');
            const proposalTemplates = @json($proposalTemplatePayload ?? []);

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

            const cleanRichText = (html) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html || '';
                wrapper.querySelectorAll('script,style,iframe,object,embed,form,input,img,video,audio,table,thead,tbody,tr,td,th,h1,h2,h3,h4,h5,h6,a').forEach((node) => node.remove());
                wrapper.querySelectorAll('*').forEach((node) => {
                    const tag = node.tagName.toLowerCase();
                    if (tag === 'b') {
                        const strong = document.createElement('strong');
                        strong.innerHTML = node.innerHTML;
                        node.replaceWith(strong);
                        return;
                    }
                    if (tag === 'i') {
                        const em = document.createElement('em');
                        em.innerHTML = node.innerHTML;
                        node.replaceWith(em);
                        return;
                    }
                    if (!['p', 'br', 'strong', 'em', 'ul', 'ol', 'li'].includes(tag)) {
                        node.replaceWith(...node.childNodes);
                        return;
                    }
                    [...node.attributes].forEach((attribute) => node.removeAttribute(attribute.name));
                });

                return wrapper.innerHTML.trim();
            };

            const updateRichTextField = (field) => {
                const editor = field.querySelector('[data-rich-text-editor]');
                const input = field.querySelector('[data-rich-text-input]');
                const count = field.querySelector('[data-rich-character-count] span');
                const warning = field.querySelector('[data-rich-long-warning]');
                if (!editor || !input) return;

                const cleaned = cleanRichText(editor.innerHTML);
                input.value = cleaned;
                const length = editor.innerText.trim().length;
                const maximum = Number(field.dataset.maxCharacters || 10000);
                if (count) count.textContent = length.toLocaleString();
                field.classList.toggle('rich-text-over-limit', length > maximum);
                if (warning) warning.classList.toggle('hidden', length <= Number(field.dataset.warningThreshold || 1200));
            };

            const bindRichTextFields = () => {
                document.querySelectorAll('[data-rich-text-field]').forEach((field) => {
                    const editor = field.querySelector('[data-rich-text-editor]');
                    let savedRange = null;
                    if (!editor) return;

                    const rangeBelongsToEditor = (range) => range
                        && editor.contains(range.commonAncestorContainer.nodeType === Node.TEXT_NODE
                            ? range.commonAncestorContainer.parentElement
                            : range.commonAncestorContainer);

                    const saveSelection = () => {
                        const selection = window.getSelection();
                        if (!selection || selection.rangeCount === 0) return;

                        const range = selection.getRangeAt(0);
                        savedRange = rangeBelongsToEditor(range) ? range.cloneRange() : null;
                        field._savedRichTextRange = savedRange;
                    };

                    const restoreSelection = () => {
                        if ((!savedRange || !rangeBelongsToEditor(savedRange)) && field._savedRichTextRange) {
                            savedRange = field._savedRichTextRange;
                        }

                        if (!savedRange || !rangeBelongsToEditor(savedRange)) {
                            editor.focus();
                            return false;
                        }

                        const selection = window.getSelection();
                        if (!selection) return false;

                        editor.focus();
                        selection.removeAllRanges();
                        selection.addRange(savedRange);

                        return true;
                    };

                    const syncAndSave = () => {
                        updateRichTextField(field);
                        saveSelection();
                    };

                    const selectedPlainTextHtml = (text) => {
                        const lines = String(text || '').split(/\r?\n/).map((line) => line.trim()).filter(Boolean);

                        if (lines.length <= 1) {
                            return escapeHtml(text);
                        }

                        return lines.map((line) => `<p>${escapeHtml(line)}</p>`).join('');
                    };

                    const linesFromFragment = (fragment) => {
                        const lines = [];
                        const pushLine = (value) => {
                            const line = String(value || '').trim();
                            if (line !== '') lines.push(line);
                        };

                        fragment.childNodes.forEach((node) => {
                            if (node.nodeType === Node.TEXT_NODE) {
                                pushLine(node.textContent);
                                return;
                            }

                            if (node.nodeType !== Node.ELEMENT_NODE) return;

                            const tag = node.tagName.toLowerCase();
                            if (tag === 'br') {
                                return;
                            }

                            if (['p', 'div', 'li'].includes(tag)) {
                                pushLine(node.textContent);
                                return;
                            }

                            const nested = linesFromFragment(node);
                            nested.forEach(pushLine);
                        });

                        return lines;
                    };

                    const selectedLines = () => {
                        const selection = window.getSelection();
                        const activeRange = selection && selection.rangeCount > 0 && rangeBelongsToEditor(selection.getRangeAt(0))
                            ? selection.getRangeAt(0)
                            : (savedRange || field._savedRichTextRange);

                        if (activeRange && !activeRange.collapsed) {
                            const fragmentLines = linesFromFragment(activeRange.cloneContents());

                            if (fragmentLines.length > 1) {
                                return fragmentLines;
                            }
                        }

                        const text = activeRange && !activeRange.collapsed ? activeRange.toString() : editor.innerText;

                        return String(text || '')
                            .split(/\r?\n/)
                            .map((line) => line.trim())
                            .filter(Boolean);
                    };

                    const insertSemanticList = (tag) => {
                        const lines = selectedLines();
                        const list = document.createElement(tag);

                        (lines.length ? lines : ['']).forEach((line) => {
                            const item = document.createElement('li');
                            if (line === '') {
                                item.appendChild(document.createElement('br'));
                            } else {
                                item.textContent = line;
                            }
                            list.appendChild(item);
                        });

                        const selection = window.getSelection();
                        const range = selection && selection.rangeCount > 0 && rangeBelongsToEditor(selection.getRangeAt(0))
                            ? selection.getRangeAt(0)
                            : savedRange;

                        if (range && rangeBelongsToEditor(range)) {
                            range.deleteContents();
                            range.insertNode(list);
                            range.setStartAfter(list);
                            range.collapse(true);
                            selection?.removeAllRanges();
                            selection?.addRange(range);
                            savedRange = range.cloneRange();
                        } else {
                            editor.appendChild(list);
                        }
                    };

                    const insertLineBreakAtCaret = () => {
                        let selection = window.getSelection();
                        if (!selection || selection.rangeCount === 0 || !rangeBelongsToEditor(selection.getRangeAt(0))) {
                            restoreSelection();
                            selection = window.getSelection();
                        }

                        if (!selection || selection.rangeCount === 0) return;

                        const range = selection.getRangeAt(0);
                        const br = document.createElement('br');
                        range.deleteContents();
                        range.insertNode(br);
                        range.setStartAfter(br);
                        range.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(range);
                        savedRange = range.cloneRange();
                        field._savedRichTextRange = savedRange;
                        syncAndSave();
                    };

                    const updateToolbarState = () => {
                        field.querySelectorAll('[data-rich-command]').forEach((button) => {
                            const command = button.dataset.richCommand;
                            let active = false;

                            if (['bold', 'italic', 'insertUnorderedList', 'insertOrderedList'].includes(command)) {
                                try {
                                    active = document.queryCommandState(command);
                                } catch (error) {
                                    active = false;
                                }
                            }

                            button.classList.toggle('border-olive-700', active);
                            button.classList.toggle('bg-olive-50', active);
                            button.classList.toggle('text-olive-900', active);
                            button.setAttribute('aria-pressed', active ? 'true' : 'false');
                        });
                    };

                    field.querySelectorAll('[data-rich-command]').forEach((button) => {
                        button.addEventListener('pointerdown', (event) => {
                            saveSelection();
                            event.preventDefault();
                        });

                        button.addEventListener('mousedown', (event) => {
                            saveSelection();
                            event.preventDefault();
                        });

                        button.addEventListener('click', () => {
                            restoreSelection();

                            if (button.dataset.richCommand === 'removeFormat') {
                                const selection = window.getSelection();
                                const selectedText = selection && selection.rangeCount > 0 && !selection.isCollapsed
                                    ? selection.toString()
                                    : '';

                                if (selectedText !== '') {
                                    document.execCommand('insertHTML', false, selectedPlainTextHtml(selectedText));
                                } else {
                                    document.execCommand('removeFormat', false, null);
                                }
                            } else if (button.dataset.richCommand === 'insertUnorderedList') {
                                insertSemanticList('ul');
                            } else if (button.dataset.richCommand === 'insertOrderedList') {
                                insertSemanticList('ol');
                            } else {
                                document.execCommand(button.dataset.richCommand, false, null);
                            }

                            editor.innerHTML = cleanRichText(editor.innerHTML);
                            syncAndSave();
                            updateToolbarState();
                        });
                    });

                    editor.addEventListener('focus', saveSelection);
                    editor.addEventListener('keyup', () => {
                        saveSelection();
                        updateToolbarState();
                    });
                    editor.addEventListener('keydown', (event) => {
                        if (event.key !== 'Enter') return;

                        event.preventDefault();
                        insertLineBreakAtCaret();
                    });
                    editor.addEventListener('beforeinput', (event) => {
                        if (!['insertParagraph', 'insertLineBreak'].includes(event.inputType)) return;

                        event.preventDefault();
                        insertLineBreakAtCaret();
                    });
                    editor.addEventListener('mouseup', () => {
                        saveSelection();
                        updateToolbarState();
                    });
                    editor.addEventListener('input', syncAndSave);
                    editor.addEventListener('paste', (event) => {
                        event.preventDefault();
                        const html = event.clipboardData?.getData('text/html');
                        const text = event.clipboardData?.getData('text/plain') || '';
                        document.execCommand('insertHTML', false, html ? cleanRichText(html) : escapeHtml(text).replace(/\n/g, '<br>'));
                        syncAndSave();
                        updateToolbarState();
                    });
                    updateRichTextField(field);
                });

                document.addEventListener('selectionchange', () => {
                    document.querySelectorAll('[data-rich-text-field]').forEach((field) => {
                        const editor = field.querySelector('[data-rich-text-editor]');
                        if (!editor || document.activeElement !== editor) return;

                        const selection = window.getSelection();
                        if (!selection || selection.rangeCount === 0) return;

                        const range = selection.getRangeAt(0);
                        const container = range.commonAncestorContainer.nodeType === Node.TEXT_NODE
                            ? range.commonAncestorContainer.parentElement
                            : range.commonAncestorContainer;

                        if (container && editor.contains(container)) {
                            field._savedRichTextRange = range.cloneRange();
                        }
                    });
                });

                document.getElementById('proposal-form')?.addEventListener('submit', () => {
                    document.querySelectorAll('[data-rich-text-field]').forEach(updateRichTextField);
                });
            };

            const updateItemDescriptionWarning = (textarea) => {
                let warning = textarea.parentElement.querySelector('[data-item-description-warning]');
                const length = textarea.value.trim().length;
                if (!warning) {
                    warning = document.createElement('p');
                    warning.dataset.itemDescriptionWarning = 'true';
                    warning.className = 'mt-2 hidden text-sm font-semibold text-amber-700';
                    warning.textContent = '{{ __('site.long_description_pdf_warning') }} {{ __('site.complete_text_included') }}';
                    textarea.insertAdjacentElement('afterend', warning);
                }
                warning.classList.toggle('hidden', length <= 420);
            };

            const bindItemDescriptionWarnings = (scope = document) => {
                scope.querySelectorAll('textarea[name*="[description]"]').forEach((textarea) => {
                    textarea.addEventListener('input', () => updateItemDescriptionWarning(textarea));
                    updateItemDescriptionWarning(textarea);
                });
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
                <div class="grid gap-4 lg:grid-cols-[0.6fr_1.4fr_0.5fr_0.45fr_0.7fr_0.7fr_auto]">
                    <div><label class="form-label">{{ __('site.item_code') }}</label><input type="hidden" name="items[${index}][category]" value="${escapeAttribute(item.category || '')}"><input name="items[${index}][item_code]" value="${escapeAttribute(item.item_code || '')}" class="form-input"></div>
                    <div><label class="form-label">{{ __('site.item_description') }}</label><textarea name="items[${index}][description]" rows="3" class="form-input" ${index === 0 ? 'required' : ''}>${escapeHtml(item.description || '')}</textarea></div>
                    <div><label class="form-label">{{ __('site.unit_abbr') }}</label><input name="items[${index}][unit]" value="${escapeAttribute(item.unit || '')}" class="form-input"></div>
                    <div><label class="form-label">{{ __('site.qty_abbr') }}</label><input data-cost-field="quantity" inputmode="numeric" name="items[${index}][quantity]" value="${escapeAttribute(item.quantity ?? '')}" class="form-input"></div>
                    <div><label class="form-label">{{ __('site.unit_value_label') }}</label><input data-cost-field="unit_value" inputmode="decimal" name="items[${index}][unit_value]" value="${escapeAttribute(item.unit_value ?? '')}" class="form-input"></div>
                    <div><label class="form-label">{{ __('site.total_value_label') }}</label><output data-line-total class="inline-flex min-h-11 w-full items-center justify-end rounded-xl bg-white px-3 font-semibold text-stone-900">—</output></div>
                    <div class="flex items-end"><button type="button" data-remove-row="item" data-confirm-message="{{ __('site.confirm_delete_item_row_message') }}" class="w-full rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button></div>
                </div>
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
                const row = document.createElement('div');
                row.className = 'proposal-item-row rounded-2xl border border-stone-200 bg-stone-50 p-4';
                row.innerHTML = itemRowHtml(index);
                itemsContainer.appendChild(row);
                bindRemove(row.querySelector('[data-remove-row="item"]'), itemsContainer, '.proposal-item-row', 'items');
                row.querySelectorAll('[data-cost-field]').forEach((field) => field.addEventListener('input', recalculateItems));
                bindItemDescriptionWarnings(row);
            });

            addTemplateButton?.addEventListener('click', () => {
                const template = proposalTemplates[templateSelect?.value];
                const copies = Math.min(20, Math.max(1, Number.parseInt(templateCopies?.value || '1', 10) || 1));

                if (!template) return;

                const alreadyPresent = [...itemsContainer.querySelectorAll('[data-template-id]')]
                    .some((row) => row.dataset.templateId === String(templateSelect.value));

                if (alreadyPresent && !window.confirm(templateSelect.dataset.templateDuplicateMessage)) {
                    return;
                }

                const rows = template.items?.length ? template.items : [{}, {}];
                let index = itemsContainer.querySelectorAll('.proposal-item-row').length;

                for (let copy = 0; copy < copies; copy += 1) {
                    rows.forEach((item) => {
                        const row = document.createElement('div');
                        row.className = 'proposal-item-row rounded-2xl border border-stone-200 bg-stone-50 p-4';
                        row.dataset.templateId = String(templateSelect.value);
                        row.innerHTML = itemRowHtml(index, item);
                        itemsContainer.appendChild(row);
                        bindRemove(row.querySelector('[data-remove-row="item"]'), itemsContainer, '.proposal-item-row', 'items');
                        row.querySelectorAll('[data-cost-field]').forEach((field) => field.addEventListener('input', recalculateItems));
                        bindItemDescriptionWarnings(row);
                        index += 1;
                    });
                }

                if (templateCopies) templateCopies.value = String(copies);
                if (templateAddedMessage) {
                    templateAddedMessage.classList.remove('hidden');
                    window.setTimeout(() => templateAddedMessage.classList.add('hidden'), 3200);
                }
                recalculateItems();
            });

            bindRemoveButtons();
            bindRichTextFields();
            bindItemDescriptionWarnings();
            itemsContainer?.querySelectorAll('[data-cost-field]').forEach((field) => field.addEventListener('input', recalculateItems));
            recalculateItems();
        });
    </script>
@endonce
