@extends('layouts.panel', ['title' => __('site.admin_settings'), 'heading' => __('site.admin_settings')])

@section('content')
    <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.branding_settings') }}</p>
            <div class="mt-6 grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
                <div>
                    <p class="form-label">{{ __('site.form_brand_favicon_current') }}</p>
                    <div class="mt-2 flex h-24 w-24 items-center justify-center rounded-2xl border border-stone-200 bg-stone-50">
                        <img src="{{ $branding['favicon_url'] }}" alt="{{ __('site.form_brand_favicon_current') }}" class="h-12 w-12 object-contain">
                    </div>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_brand_favicon_replace') }}</label>
                    <input type="file" name="brand_favicon" class="form-input" accept=".png,.ico,image/png,image/x-icon">
                    <p class="mt-2 text-xs text-stone-500">{{ __('site.form_brand_favicon_help') }}</p>
                    <p class="mt-1 text-xs text-stone-500">{{ __('site.form_brand_favicon_recommended') }}</p>
                    <label class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-stone-700">
                        <input type="checkbox" name="restore_brand_favicon" value="1">
                        {{ __('site.form_brand_favicon_restore') }}
                    </label>
                </div>
            </div>
        </section>

        @foreach ($settings as $group => $items)
            @php
                $visibleItems = $items->reject(fn ($setting) => in_array($setting->key, $hiddenSettingKeys, true));
            @endphp
            @continue($visibleItems->isEmpty())
            <section class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __("site.settings_group_{$group}") }}</p>
                <div class="mt-6 grid gap-5">
                    @foreach ($visibleItems as $setting)
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
