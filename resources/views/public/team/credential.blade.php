@extends('layouts.public')

@section('content')
    <section class="mx-auto max-w-6xl px-6 py-12 lg:px-8">
        <a href="{{ route('team.show', $teamMember->slug) }}" class="text-sm font-semibold text-olive-700">{{ __('site.back_to_team') }}</a>

        <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $teamMember->name }}</p>
            <h1 class="mt-3 text-3xl font-semibold text-stone-950">{{ $credential->title }}</h1>
            <p class="mt-3 text-sm leading-7 text-stone-600">{{ __('site.credential_viewer_notice') }}</p>

            <div class="mt-8 overflow-hidden rounded-[2rem] border border-stone-200 bg-stone-100 p-4">
                <div class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">
                    {{ __('site.credential_uncontrolled_warning') }}
                </div>
                @if (! $fileExists)
                    <div class="p-10 text-base leading-7 text-stone-600">{{ __('site.credential_file_missing') }}</div>
                @elseif ($credential->isPdf())
                    <div class="rounded-2xl bg-white p-3 shadow-sm">
                        <div class="mb-3 flex flex-col gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4 md:flex-row md:items-center md:justify-between">
                            <p class="text-sm leading-6 text-stone-600">{{ __('site.protected_pdf_download_note') }}</p>
                            <a href="{{ URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(10), [$teamMember, $credential]) }}" target="_blank" rel="noopener" class="inline-flex justify-center rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-olive-800">
                                {{ __('site.open_pdf_new_tab') }}
                            </a>
                        </div>
                        <object
                            data="{{ URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(10), [$teamMember, $credential]) }}#toolbar=1&navpanes=1"
                            type="application/pdf"
                            class="h-[78vh] w-full rounded-xl border border-stone-200 bg-white"
                        >
                            <div class="p-8 text-base leading-7 text-stone-600">
                                <p>{{ __('site.credential_not_previewable') }}</p>
                                <a href="{{ URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(10), [$teamMember, $credential]) }}" target="_blank" rel="noopener" class="mt-5 inline-flex rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-olive-800">
                                    {{ __('site.open_pdf_new_tab') }}
                                </a>
                            </div>
                        </object>
                    </div>
                @elseif ($credential->hasRenderablePreview())
                    <div class="space-y-5">
                        @for ($page = 1; $page <= max(1, $credential->preview_page_count); $page++)
                            <img
                                src="{{ URL::temporarySignedRoute('team.credentials.preview', now()->addMinutes(20), [$teamMember, $credential, $page]) }}"
                                alt="{{ $credential->title }} - {{ __('site.page_number', ['page' => $page]) }}"
                                class="mx-auto w-full max-w-5xl rounded-xl bg-white shadow-sm"
                                draggable="false"
                            >
                        @endfor
                    </div>
                @else
                    <div class="p-10 text-base leading-7 text-stone-600">
                        <p>{{ __('site.credential_not_previewable') }}</p>
                        <a href="{{ URL::temporarySignedRoute('team.credentials.file', now()->addMinutes(10), [$teamMember, $credential]) }}" target="_blank" rel="noopener" class="mt-5 inline-flex rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-olive-800">
                            {{ __('site.open_pdf_new_tab') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
