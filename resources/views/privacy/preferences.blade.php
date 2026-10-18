<section class="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-xl border border-stone-300 bg-white p-5 shadow-xl sm:p-6" aria-labelledby="privacy-preferences-heading" role="region">
    <h2 id="privacy-preferences-heading" class="text-lg font-semibold text-stone-950">{{ __('site.privacy_preferences_heading') }}</h2>
    <p class="mt-2 text-sm leading-6 text-stone-600">{{ __('site.privacy_preferences_intro') }}</p>
    <form method="POST" action="{{ route('privacy.preferences.store') }}" class="mt-5 space-y-4">
        @csrf
        <label class="flex items-start gap-3 text-sm text-stone-700">
            <input type="checkbox" name="analytics" value="1" class="mt-1 h-4 w-4 border-stone-400 text-olive-700 focus:ring-olive-700">
            <span><strong>{{ __('site.privacy_analytics_label') }}</strong><br>{{ __('site.privacy_analytics_description') }}</span>
        </label>
        <label class="flex items-start gap-3 text-sm text-stone-700">
            <input type="checkbox" name="advertising" value="1" class="mt-1 h-4 w-4 border-stone-400 text-olive-700 focus:ring-olive-700">
            <span><strong>{{ __('site.privacy_advertising_label') }}</strong><br>{{ __('site.privacy_advertising_description') }}</span>
        </label>
        <div class="flex flex-wrap gap-3 pt-1">
            <button type="submit" name="action" value="save" class="rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white hover:bg-olive-800">{{ __('site.privacy_save_preferences') }}</button>
            <button type="submit" name="action" value="reject" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-800 hover:border-olive-700">{{ __('site.privacy_reject_optional') }}</button>
        </div>
    </form>
</section>
