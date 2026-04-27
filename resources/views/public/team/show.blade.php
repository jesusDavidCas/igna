@extends('layouts.public', ['title' => $profile['name']])

@section('content')
    <section class="mx-auto max-w-5xl px-6 py-16 lg:px-8">
        <a href="{{ route('home') }}#team" class="text-sm font-semibold text-olive-700">{{ __('site.back_to_team') }}</a>
        <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $profile['role'] }}</p>
            <h1 class="mt-3 text-4xl font-semibold text-stone-950">{{ $profile['name'] }}</h1>
            <div class="mt-8 space-y-5 text-sm leading-8 text-stone-600">
                @foreach ($profile['bio'] as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>
            <div class="mt-10">
                <h2 class="text-lg font-semibold text-stone-950">{{ __('site.expertise') }}</h2>
                <div class="mt-4 flex flex-wrap gap-3">
                    @foreach ($profile['expertise'] as $item)
                        <span class="rounded-full bg-olive-50 px-4 py-2 text-sm text-olive-900">{{ $item }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
