@extends('layouts.public')

@section('content')
    <section class="mx-auto max-w-5xl px-6 py-16 lg:px-8">
        <a href="{{ route('home') }}#team" class="text-sm font-semibold text-olive-700">{{ __('site.back_to_team') }}</a>
        <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <div class="flex flex-col gap-6 md:flex-row md:items-start">
                <x-team.photo :member="$profile" variant="profile" />
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $profile->role }}</p>
                    <h1 class="mt-3 text-4xl font-semibold text-stone-950">{{ $profile->name }}</h1>
                    <p class="mt-4 text-[17px] leading-8 text-stone-600">{{ $profile->short_description }}</p>
                </div>
            </div>
            <div class="mt-8 space-y-5 text-[17px] leading-8 text-stone-600">
                @foreach ($profile->bio ?? [] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
            <div class="mt-10">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.expertise') }}</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    @foreach ($profile->expertise ?? [] as $item)
                        <span class="rounded-full bg-olive-50 px-4 py-2 text-sm text-olive-900">{{ $item }}</span>
                    @endforeach
                </div>
            </div>

            <div class="mt-10 border-t border-stone-200 pt-8">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.credentials') }}</h2>
                <p class="mt-2 text-[16px] leading-7 text-stone-500">{{ __('site.credentials_secure_note') }}</p>
                <div class="mt-5 grid gap-4">
                    @forelse ($profile->publicCredentials as $credential)
                        <article class="rounded-2xl border border-stone-200 bg-stone-50 p-5">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="font-semibold text-stone-950">{{ $credential->title }}</p>
                                    <p class="mt-1 text-[15px] leading-6 text-stone-500">
                                        {{ $credential->credential_type ?: __('site.credential_document') }}
                                        @if ($credential->institution)
                                            · {{ $credential->institution }}
                                        @endif
                                        @if ($credential->issued_at)
                                            · {{ $credential->issued_at->format('Y') }}
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ URL::temporarySignedRoute('team.credentials.show', now()->addMinutes(20), [$profile, $credential]) }}" class="rounded-full bg-olive-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-olive-800">
                                    {{ __('site.view_credential') }}
                                </a>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-stone-500">{{ __('site.no_credentials') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
