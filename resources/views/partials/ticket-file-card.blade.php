<div class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-base font-semibold text-stone-900">{{ $file->title }}</p>
            <p class="mt-1 text-[15px] leading-6 text-stone-500">{{ $file->original_name }}</p>
            <div class="mt-3 flex flex-wrap gap-2 text-[15px]">
                <span class="rounded-full bg-stone-100 px-3 py-1 text-stone-600">{{ $file->deliveryTypeLabel() }}</span>
                <span class="rounded-full px-3 py-1 {{ $file->is_client_visible ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">
                    {{ $file->is_client_visible ? __('site.client_visible') : __('site.internal_only') }}
                </span>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $downloadUrl }}" class="inline-flex items-center justify-center rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-olive-800">
                {{ __('site.download_file') }}
            </a>
            @isset($visibilityRoute)
                <form method="POST" action="{{ $visibilityRoute }}" data-confirm-title="{{ __('site.confirm_file_visibility_title') }}" data-confirm-message="{{ __('site.confirm_file_visibility_message') }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-olive-500 hover:text-olive-800">
                        {{ $file->is_client_visible ? __('site.hide_from_client') : __('site.make_available_to_client') }}
                    </button>
                </form>
            @endisset
            @isset($deleteRoute)
                <form method="POST" action="{{ $deleteRoute }}" data-confirm-title="{{ __('site.confirm_delete_file_title') }}" data-confirm-message="{{ __('site.confirm_delete_file_message') }}" data-confirm-danger="true">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center justify-center rounded-full border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-50">
                        {{ __('site.delete') }}
                    </button>
                </form>
            @endisset
        </div>
    </div>
</div>
