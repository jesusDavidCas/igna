@php
    $contentLocale = app()->getLocale() === 'es' ? 'es' : 'en';
    $targetLocale = $contentLocale === 'es' ? 'en' : 'es';
    $localizedDeliverables = $service->exists ? $service->localizedDeliverables() : [];
@endphp

<form id="service-form" method="POST" action="{{ $action }}" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <input type="hidden" name="content_locale" value="{{ $contentLocale }}">

    <div class="grid gap-5 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="form-label">{{ __('site.form_name') }}</label>
            <input name="name" value="{{ old('name', old("name_{$contentLocale}", $contentLocale === 'es' ? ($service->name_es ?: $service->localizedName()) : ($service->name_en ?: $service->localizedName()))) }}" class="form-input" required>
            <input type="hidden" name="name_{{ $targetLocale }}" value="{{ old("name_{$targetLocale}", $targetLocale === 'es' ? $service->name_es : $service->name_en) }}">
        </div>
        <div>
            <label class="form-label">{{ __('site.form_code') }}</label>
            <input name="code" value="{{ old('code', $service->code) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_public_service_category') }}</label>
            <select name="business_line" class="form-input" required>
                <option value="digital" @selected(old('business_line', $service->business_line) === 'digital')>{{ __('site.service_public_category_technology') }}</option>
                <option value="engineering" @selected(old('business_line', $service->business_line) === 'engineering')>{{ __('site.service_public_category_infrastructure_engineering') }}</option>
            </select>
            <p class="mt-2 text-sm text-stone-500">{{ __('site.form_public_service_category_help') }}</p>
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
            <label class="form-label">{{ __('site.form_description') }}</label>
            <textarea name="description" rows="4" class="form-input">{{ old('description', old("description_{$contentLocale}", $contentLocale === 'es' ? ($service->description_es ?: $service->localizedDescription()) : ($service->description_en ?: $service->localizedDescription()))) }}</textarea>
            <input type="hidden" name="description_{{ $targetLocale }}" value="{{ old("description_{$targetLocale}", $targetLocale === 'es' ? $service->description_es : $service->description_en) }}">
        </div>
        <div class="md:col-span-2">
            @php
                $deliverableRows = old('deliverables');
                if (! is_array($deliverableRows)) {
                    $deliverableRows = $service->relationLoaded('deliverables') && $service->deliverables->isNotEmpty()
                        ? $service->deliverables->values()->map(fn ($deliverable, int $index) => [
                            'id' => $deliverable->id,
                            'en' => $deliverable->name_en ?: $deliverable->name,
                            'es' => $deliverable->name_es,
                            'content' => $contentLocale === 'es' ? ($localizedDeliverables[$index] ?? $deliverable->localizedName()) : ($deliverable->name_en ?: $deliverable->localizedName()),
                        ])->values()->all()
                        : collect($service->deliverables_schema ?? [])->map(fn ($deliverable) => is_array($deliverable)
                            ? ['id' => $deliverable['id'] ?? null, 'en' => $deliverable['en'] ?? '', 'es' => $deliverable['es'] ?? '', 'content' => $deliverable[$contentLocale] ?? $deliverable['en'] ?? $deliverable['es'] ?? '']
                            : ['id' => null, 'en' => (string) $deliverable, 'es' => '', 'content' => (string) $deliverable])->values()->all();
                }
                if ($deliverableRows === []) {
                    $deliverableRows = [['id' => null, 'en' => '', 'es' => '', 'content' => '']];
                }
            @endphp
            <div class="flex items-center justify-between gap-4">
                <label class="form-label">{{ __('site.form_deliverables') }}</label>
                <button type="button" data-add-deliverable class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.add_deliverable') }}</button>
            </div>
            <div data-deliverables-list class="mt-3 space-y-3">
                @foreach ($deliverableRows as $index => $deliverable)
                    <div data-deliverable-row class="grid gap-3 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_auto]">
                        <div>
                            <label class="form-label" for="deliverable-{{ $index }}-content">{{ __('site.form_deliverable') }}</label>
                            <input type="hidden" name="deliverables[{{ $index }}][id]" value="{{ $deliverable['id'] ?? '' }}">
                            <input id="deliverable-{{ $index }}-content" name="deliverables[{{ $index }}][content]" value="{{ $deliverable['content'] ?? $deliverable[$contentLocale] ?? '' }}" class="form-input">
                            <input type="hidden" name="deliverables[{{ $index }}][{{ $targetLocale }}]" value="{{ $deliverable[$targetLocale] ?? '' }}">
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

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            @if ($service->exists && auth()->user()->isSuperAdmin())
                <button
                    type="button"
                    class="rounded-full border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition enabled:hover:border-rose-700 enabled:hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50"
                    data-delete-modal-trigger="delete-service-{{ $service->id }}"
                    @disabled(! ($deletionImpact ?? null)?->canDelete())
                >
                    {{ __('site.deletion_compact_title_service') }}
                </button>
                @if (! ($deletionImpact ?? null)?->canDelete())
                    <p class="mt-2 max-w-xl text-sm leading-6 text-amber-800" data-delete-blocked-message>{{ __('site.service_delete_dependency_blocked') }}</p>
                @endif
                @error('deletion') <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            @endif
        </div>
        <div class="flex flex-col gap-3 sm:items-end">
            <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
            @if ($service->exists)
                <p class="text-sm leading-6 text-stone-500">{{ __('site.dynamic_translation_cache_note') }}</p>
            @endif
        </div>
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
            row.className = 'grid gap-3 rounded-2xl bg-stone-50 p-4 md:grid-cols-[1fr_auto]';
            row.innerHTML = `
                <div>
                    <label class="form-label" for="deliverable-${index}-content">{{ __('site.form_deliverable') }}</label>
                    <input type="hidden" name="deliverables[${index}][id]">
                    <input id="deliverable-${index}-content" name="deliverables[${index}][content]" class="form-input">
                    <input type="hidden" name="deliverables[${index}][{{ $targetLocale }}]">
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
