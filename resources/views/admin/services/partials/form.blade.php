<form method="POST" action="{{ $action }}" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label">{{ __('site.form_name') }}</label>
            <input name="name" value="{{ old('name', $service->name) }}" class="form-input" required>
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
            <label class="form-label">{{ __('site.form_description') }}</label>
            <textarea name="description" rows="5" class="form-input">{{ old('description', $service->description) }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">{{ __('site.form_deliverables') }}</label>
            <textarea name="deliverables" rows="6" class="form-input">{{ old('deliverables', is_array($service->deliverables_schema) ? implode("\n", $service->deliverables_schema) : '') }}</textarea>
            <p class="mt-2 text-xs text-stone-500">{{ __('site.form_deliverables_help') }}</p>
        </div>
    </div>

    <button type="submit" class="mt-6 rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
</form>
