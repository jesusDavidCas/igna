@extends('layouts.panel', ['title' => __('site.admin_settings'), 'heading' => __('site.admin_settings')])

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.branding_settings') }}</p>
            <div class="mt-6 grid gap-5 md:grid-cols-2">
                <div>
                    <label class="form-label">{{ __('site.form_brand_logo') }}</label>
                    <input type="file" name="brand_logo" class="form-input" accept=".png,.jpg,.jpeg,.webp">
                    <p class="mt-2 text-xs text-stone-500">{{ __('site.form_brand_logo_help') }}</p>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_brand_favicon') }}</label>
                    <input type="file" name="brand_favicon" class="form-input" accept=".png,.ico">
                    <p class="mt-2 text-xs text-stone-500">{{ __('site.form_brand_favicon_help') }}</p>
                </div>
            </div>
        </section>

        @foreach ($settings as $group => $items)
            <section class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __("site.settings_group_{$group}") }}</p>
                <div class="mt-6 grid gap-5">
                    @foreach ($items as $setting)
                        <div>
                            <label class="form-label">{{ __("site.settings_key_{$setting->key}") }}</label>
                            <textarea name="settings[{{ $setting->key }}]" rows="2" class="form-input">{{ old("settings.{$setting->key}", $setting->value) }}</textarea>
                            <p class="mt-2 text-xs text-stone-500">{{ $setting->key }} · {{ $setting->type }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
    </form>
@endsection
