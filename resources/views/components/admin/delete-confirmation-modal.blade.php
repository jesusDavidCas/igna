@props([
    'id',
    'action',
    'title',
    'question',
    'identifier',
    'consequence' => null,
])

<div
    id="{{ $id }}"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-stone-950/55 px-4 py-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
    aria-describedby="{{ $id }}-description"
    data-delete-modal
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 id="{{ $id }}-title" class="text-lg font-semibold text-stone-950">{{ $title }}</h2>
        <p id="{{ $id }}-description" class="mt-3 text-sm leading-6 text-stone-600">
            {{ $question }}
            <span class="font-semibold text-stone-950">&ldquo;{{ $identifier }}&rdquo;</span>?
        </p>
        @if ($consequence)
            <p class="mt-3 rounded-xl bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-900">{{ $consequence }}</p>
        @endif

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 transition hover:border-stone-500" data-delete-modal-cancel>
                {{ __('site.cancel') }}
            </button>
            <form method="POST" action="{{ $action }}" data-delete-modal-form>
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full rounded-full bg-rose-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800 disabled:cursor-wait disabled:opacity-70 sm:w-auto" data-delete-modal-submit>
                    {{ __('site.deletion_submit') }}
                </button>
            </form>
        </div>
    </div>
</div>
