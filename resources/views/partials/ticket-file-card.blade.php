<div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-stone-900">{{ $file->title }}</p>
            <p class="mt-1 text-xs text-stone-500">{{ $file->original_name }}</p>
        </div>
        <a href="{{ $downloadUrl }}" class="inline-flex items-center justify-center rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-olive-800">
            {{ __('site.download_file') }}
        </a>
    </div>
</div>
