@props([
    'action',
    'title',
    'warning',
    'modalId',
    'modalTitle',
    'modalQuestion',
    'identifier',
    'modalConsequence' => null,
    'blockedMessage' => null,
    'canDelete' => true,
])

@if (auth()->user()->isSuperAdmin())
    <section class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm print:hidden" data-compact-delete-control>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-stone-950">{{ $title }}</h2>
                <p class="mt-1 text-sm leading-6 text-stone-600">{{ $warning }}</p>
                @if ($blockedMessage || ! $canDelete)
                    <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-sm leading-6 text-amber-900" data-delete-blocked-message>
                        {{ $blockedMessage ?: __('site.deletion_blocked_by_dependencies') }}
                    </p>
                @endif
                @error('deletion') <p class="mt-3 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <button
                type="button"
                class="w-full rounded-full border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-700 transition enabled:hover:border-rose-700 enabled:hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto sm:shrink-0"
                data-delete-modal-trigger="{{ $modalId }}"
                @disabled($blockedMessage || ! $canDelete)
            >
                {{ __('site.deletion_submit') }}
            </button>
        </div>
    </section>

    @if (! $blockedMessage && $canDelete)
        <x-admin.delete-confirmation-modal
            :id="$modalId"
            :action="$action"
            :title="$modalTitle"
            :question="$modalQuestion"
            :identifier="$identifier"
            :consequence="$modalConsequence"
        />
    @endif
@endif
