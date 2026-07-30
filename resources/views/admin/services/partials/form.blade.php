<form id="service-form" method="POST" action="{{ $action }}" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label">{{ __('site.form_name') }} EN</label>
            <input name="name_en" value="{{ old('name_en', $service->name_en ?: $service->name) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_name') }} ES</label>
            <input name="name_es" value="{{ old('name_es', $service->name_es) }}" class="form-input">
        </div>
        <div>
            <label class="form-label">{{ __('site.form_code') }}</label>
            <input name="code" value="{{ old('code', $service->code) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_business_line') }}</label>
            <select name="business_line" class="form-input" required>
                <option value="digital" @selected(old('business_line', $service->business_line) === 'digital')>{{ __('site.business_line_digital') }}</option>
                <option value="engineering" @selected(old('business_line', $service->business_line) === 'engineering')>{{ __('site.business_line_engineering') }}</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_service_type') }}</label>
            <select name="service_type" class="form-input" required>
                @foreach ($serviceTypes as $line => $types)
                    <optgroup label="{{ $line === 'digital' ? __('site.business_line_digital') : __('site.business_line_engineering') }}">
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('service_type', $service->service_type) === $value)>{{ __($label) }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_service_scope') }}</label>
            <select name="service_scope" class="form-input" required>
                @foreach ($serviceScopes as $value => $label)
                    <option value="{{ $value }}" @selected(old('service_scope', $service->service_scope) === $value)>{{ __($label) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active ?? true))>
                {{ __('site.active') }}
            </label>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">{{ __('site.form_description') }} EN</label>
            <textarea name="description_en" rows="4" class="form-input">{{ old('description_en', $service->description_en ?: $service->description) }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">{{ __('site.form_description') }} ES</label>
            <textarea name="description_es" rows="4" class="form-input">{{ old('description_es', $service->description_es) }}</textarea>
        </div>
        <div class="md:col-span-2">
            @php
                $deliverableRows = old('deliverables');
                if (! is_array($deliverableRows)) {
                    $deliverableRows = $service->relationLoaded('deliverables') && $service->deliverables->isNotEmpty()
                        ? $service->deliverables->map(fn ($deliverable) => [
                            'en' => $deliverable->name_en ?: $deliverable->name,
                            'es' => $deliverable->name_es,
                        ])->values()->all()
                        : collect($service->deliverables_schema ?? [])->map(fn ($deliverable) => is_array($deliverable)
                            ? ['en' => $deliverable['en'] ?? '', 'es' => $deliverable['es'] ?? '']
                            : ['en' => (string) $deliverable, 'es' => ''])->values()->all();
                }
                if ($deliverableRows === []) {
                    $deliverableRows = [['en' => '', 'es' => '']];
                }
            @endphp
            <div class="flex items-center justify-between gap-4">
                <label class="form-label">{{ __('site.form_deliverables') }}</label>
                <button type="button" data-add-deliverable class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_deliverable') }}</button>
            </div>
            <div data-deliverables-list class="mt-3 space-y-3">
                @foreach ($deliverableRows as $index => $deliverable)
                    <div data-deliverable-row class="grid gap-3 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_1fr_auto]">
                        <div>
                            <label class="form-label" for="deliverable-{{ $index }}-en">{{ __('site.deliverable_en') }}</label>
                            <input id="deliverable-{{ $index }}-en" name="deliverables[{{ $index }}][en]" value="{{ $deliverable['en'] ?? '' }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label" for="deliverable-{{ $index }}-es">{{ __('site.deliverable_es') }}</label>
                            <input id="deliverable-{{ $index }}-es" name="deliverables[{{ $index }}][es]" value="{{ $deliverable['es'] ?? '' }}" class="form-input">
                        </div>
                        <div class="flex items-end">
                            <button type="button" data-remove-deliverable class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove_deliverable') }}</button>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-2 text-sm text-stone-500">{{ __('site.form_deliverables_help') }}</p>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
        <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
        @if ($service->exists)
            <button type="submit" name="source_locale" value="es" formaction="{{ route('admin.services.translate', $service) }}" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-700">{{ __('site.translate_from_spanish') }}</button>
            <button type="submit" name="source_locale" value="en" formaction="{{ route('admin.services.translate', $service) }}" class="rounded-full border border-stone-300 px-5 py-2.5 text-sm font-semibold text-stone-700">{{ __('site.translate_from_english') }}</button>
            <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="overwrite" value="1">
                {{ __('site.overwrite_translations') }}
            </label>
        @endif
    </div>
</form>

<script>
    document.addEventListener('click', (event) => {
        const addButton = event.target.closest('[data-add-deliverable]');
        const removeButton = event.target.closest('[data-remove-deliverable]');
        const list = document.querySelector('[data-deliverables-list]');

        if (addButton && list) {
            const index = list.querySelectorAll('[data-deliverable-row]').length;
            const row = document.createElement('div');
            row.setAttribute('data-deliverable-row', '');
            row.className = 'grid gap-3 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_1fr_auto]';
            row.innerHTML = `
                <div>
                    <label class="form-label" for="deliverable-${index}-en">{{ __('site.deliverable_en') }}</label>
                    <input id="deliverable-${index}-en" name="deliverables[${index}][en]" class="form-input">
                </div>
                <div>
                    <label class="form-label" for="deliverable-${index}-es">{{ __('site.deliverable_es') }}</label>
                    <input id="deliverable-${index}-es" name="deliverables[${index}][es]" class="form-input">
                </div>
                <div class="flex items-end">
                    <button type="button" data-remove-deliverable class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove_deliverable') }}</button>
                </div>`;
            list.appendChild(row);
            row.querySelector('input')?.focus();
        }

        if (removeButton) {
            const row = removeButton.closest('[data-deliverable-row]');
            if (row && list && list.querySelectorAll('[data-deliverable-row]').length > 1) {
                row.remove();
            }
        }
    });
</script>
