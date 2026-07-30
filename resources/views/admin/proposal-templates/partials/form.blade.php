@php
    $fieldClass = fn (string $name): string => 'form-input'.($errors->has($name) ? ' border-rose-500 ring-2 ring-rose-100' : '');
    $errorId = fn (string $name): string => 'error-'.str_replace(['.', '_'], '-', $name);
    $errorAttributes = fn (string $name): string => $errors->has($name) ? 'aria-invalid="true" aria-describedby="'.$errorId($name).'"' : '';
    $rows = old('items', $items);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-8" data-proposal-template-form>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.proposal-templates.index') }}" class="text-sm font-semibold text-olive-700">{{ __('site.back_to_templates') }}</a>
        <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_template') }}</button>
    </div>

    <section class="rounded-[1rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">{{ __('site.proposal_template_identity') }}</p>
            <h2 class="mt-2 text-2xl font-semibold text-stone-950">{{ __('site.proposal_template_titles') }}</h2>
        </div>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label for="template-name-en" class="form-label">{{ __('site.proposal_template_title_en') }}</label>
                <input id="template-name-en" name="name_en" value="{{ old('name_en', $proposalTemplate->name_en) }}" class="{{ $fieldClass('name_en') }}" required {!! $errorAttributes('name_en') !!}>
                @error('name_en') <p id="{{ $errorId('name_en') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="template-name-es" class="form-label">{{ __('site.proposal_template_title_es') }}</label>
                <input id="template-name-es" name="name_es" value="{{ old('name_es', $proposalTemplate->name_es) }}" class="{{ $fieldClass('name_es') }}" required {!! $errorAttributes('name_es') !!}>
                @error('name_es') <p id="{{ $errorId('name_es') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="template-code" class="form-label">{{ __('site.proposal_template_code') }}</label>
                <input id="template-code" name="code" value="{{ old('code', $proposalTemplate->code) }}" class="{{ $fieldClass('code') }}" required {!! $errorAttributes('code') !!}>
                @error('code') <p id="{{ $errorId('code') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="template-number" class="form-label">{{ __('site.proposal_template_number') }}</label>
                    <input id="template-number" type="number" min="1" max="9999" name="service_number" value="{{ old('service_number', $proposalTemplate->service_number) }}" class="{{ $fieldClass('service_number') }}" required {!! $errorAttributes('service_number') !!}>
                    @error('service_number') <p id="{{ $errorId('service_number') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="template-sort" class="form-label">{{ __('site.proposal_template_sort_order') }}</label>
                    <input id="template-sort" type="number" min="0" max="9999" name="sort_order" value="{{ old('sort_order', $proposalTemplate->sort_order) }}" class="{{ $fieldClass('sort_order') }}" required {!! $errorAttributes('sort_order') !!}>
                    @error('sort_order') <p id="{{ $errorId('sort_order') }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                </div>
            </div>
            <label class="flex items-center gap-3 rounded-2xl bg-stone-50 p-4 text-sm font-semibold text-stone-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 rounded border-stone-300 text-olive-700" @checked(old('is_active', ($proposalTemplate->is_active ?? true) ? '1' : '0') === '1')>
                {{ __('site.active') }}
            </label>
        </div>
    </section>

    <section class="rounded-[1rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">{{ __('site.proposal_template_items') }}</p>
            <p class="mt-2 text-sm leading-6 text-stone-500">{{ __('site.proposal_template_items_help') }}</p>
        </div>

        <div id="proposal-template-items" class="mt-6 space-y-4">
            @foreach ($rows as $index => $item)
                <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4" data-template-row>
                    <div class="grid gap-4 lg:grid-cols-[0.45fr_1fr_1fr_0.35fr_0.4fr_0.55fr_auto]">
                        <div>
                            <label class="form-label" for="template-item-code-{{ $index }}">{{ __('site.template_row_code') }}</label>
                            <input id="template-item-code-{{ $index }}" name="items[{{ $index }}][item_code]" value="{{ $item['item_code'] ?? '' }}" class="{{ $fieldClass("items.$index.item_code") }}" {!! $errorAttributes("items.$index.item_code") !!}>
                            @error("items.$index.item_code") <p id="{{ $errorId("items.$index.item_code") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="template-item-en-{{ $index }}">{{ __('site.template_row_en') }}</label>
                            <textarea id="template-item-en-{{ $index }}" name="items[{{ $index }}][description_en]" rows="3" class="{{ $fieldClass("items.$index.description_en") }}" required {!! $errorAttributes("items.$index.description_en") !!}>{{ $item['description_en'] ?? '' }}</textarea>
                            @error("items.$index.description_en") <p id="{{ $errorId("items.$index.description_en") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="template-item-es-{{ $index }}">{{ __('site.template_row_es') }}</label>
                            <textarea id="template-item-es-{{ $index }}" name="items[{{ $index }}][description_es]" rows="3" class="{{ $fieldClass("items.$index.description_es") }}" required {!! $errorAttributes("items.$index.description_es") !!}>{{ $item['description_es'] ?? '' }}</textarea>
                            @error("items.$index.description_es") <p id="{{ $errorId("items.$index.description_es") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="template-item-unit-{{ $index }}">{{ __('site.template_row_unit') }}</label>
                            <input id="template-item-unit-{{ $index }}" name="items[{{ $index }}][unit]" value="{{ $item['unit'] ?? '' }}" class="{{ $fieldClass("items.$index.unit") }}" {!! $errorAttributes("items.$index.unit") !!}>
                            @error("items.$index.unit") <p id="{{ $errorId("items.$index.unit") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="template-item-qty-{{ $index }}">{{ __('site.qty_abbr') }}</label>
                            <input id="template-item-qty-{{ $index }}" inputmode="numeric" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? '' }}" class="{{ $fieldClass("items.$index.quantity") }}" {!! $errorAttributes("items.$index.quantity") !!}>
                            @error("items.$index.quantity") <p id="{{ $errorId("items.$index.quantity") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label" for="template-item-value-{{ $index }}">{{ __('site.unit_value_label') }}</label>
                            <input id="template-item-value-{{ $index }}" inputmode="decimal" name="items[{{ $index }}][unit_value]" value="{{ $item['unit_value'] ?? '' }}" class="{{ $fieldClass("items.$index.unit_value") }}" {!! $errorAttributes("items.$index.unit_value") !!}>
                            @error("items.$index.unit_value") <p id="{{ $errorId("items.$index.unit_value") }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end">
                            <button type="button" data-remove-template-row class="w-full rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @error('items') <p id="{{ $errorId('items') }}" class="mt-3 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror

        <button type="button" data-add-template-row class="mt-4 rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_item') }}</button>
    </section>
</form>

<template id="proposal-template-row-template">
    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4" data-template-row>
        <div class="grid gap-4 lg:grid-cols-[0.45fr_1fr_1fr_0.35fr_0.4fr_0.55fr_auto]">
            <div>
                <label class="form-label">{{ __('site.template_row_code') }}</label>
                <input data-template-field="item_code" class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('site.template_row_en') }}</label>
                <textarea data-template-field="description_en" rows="3" class="form-input" required></textarea>
            </div>
            <div>
                <label class="form-label">{{ __('site.template_row_es') }}</label>
                <textarea data-template-field="description_es" rows="3" class="form-input" required></textarea>
            </div>
            <div>
                <label class="form-label">{{ __('site.template_row_unit') }}</label>
                <input data-template-field="unit" class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('site.qty_abbr') }}</label>
                <input data-template-field="quantity" inputmode="numeric" class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('site.unit_value_label') }}</label>
                <input data-template-field="unit_value" inputmode="decimal" class="form-input">
            </div>
            <div class="flex items-end">
                <button type="button" data-remove-template-row class="w-full rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove') }}</button>
            </div>
        </div>
    </div>
</template>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('proposal-template-items');
        const template = document.getElementById('proposal-template-row-template');
        const addButton = document.querySelector('[data-add-template-row]');

        const reindexRows = () => {
            container.querySelectorAll('[data-template-row]').forEach((row, index) => {
                row.querySelectorAll('[data-template-field], input[name^="items["], textarea[name^="items["]').forEach((field) => {
                    const key = field.dataset.templateField || (field.name.match(/\]\[([^\]]+)\]$/) || [])[1];
                    if (!key) return;

                    field.name = `items[${index}][${key}]`;
                    field.id = `template-item-${key.replace('_', '-')}-${index}`;
                    const label = field.closest('div')?.querySelector('label');
                    if (label) label.setAttribute('for', field.id);
                });
            });
        };

        addButton?.addEventListener('click', () => {
            container.appendChild(template.content.firstElementChild.cloneNode(true));
            reindexRows();
        });

        container?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-template-row]');
            if (!button) return;

            const rows = container.querySelectorAll('[data-template-row]');
            if (rows.length <= 1) return;

            button.closest('[data-template-row]').remove();
            reindexRows();
        });
    });
</script>
