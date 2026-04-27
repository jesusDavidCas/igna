<form method="POST" action="{{ $action }}" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label">{{ __('site.form_first_name') }}</label>
            <input name="first_name" value="{{ old('first_name', $user->first_name) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_last_name') }}</label>
            <input name="last_name" value="{{ old('last_name', $user->last_name) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_email') }}</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_phone') }}</label>
            <input name="phone" value="{{ old('phone', $user->phone) }}" class="form-input">
        </div>
        <div>
            <label class="form-label">{{ __('site.form_preferred_language') }}</label>
            <select name="preferred_language" class="form-input" required>
                <option value="es" @selected(old('preferred_language', $user->preferred_language) === 'es')>{{ __('site.language_spanish') }}</option>
                <option value="en" @selected(old('preferred_language', $user->preferred_language) === 'en')>{{ __('site.language_english') }}</option>
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_role') }}</label>
            <select name="role" class="form-input" required>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_password') }}</label>
            <input type="password" name="password" class="form-input" @required($method === 'POST')>
            @if ($method !== 'POST')
                <p class="mt-2 text-xs text-stone-500">{{ __('site.password_blank_help') }}</p>
            @endif
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true))>
                {{ __('site.active') }}
            </label>
        </div>
    </div>

    <button type="submit" class="mt-6 rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
</form>
