@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-5xl px-6 py-14 sm:py-16 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-olive-700">{{ __('site.back_to_blog') }}</a>
            <h1 class="mt-6 text-4xl font-semibold leading-[1.06] text-stone-950 sm:text-5xl">{{ $post->localizedTitle() }}</h1>
            <p class="mt-4 text-sm font-medium uppercase tracking-[0.12em] text-stone-500">{{ optional($post->published_at)->format('Y-m-d') }}</p>
            <p class="mt-7 text-xl leading-8 text-stone-600">{{ $post->localizedSummary() }}</p>
        </div>

        @if ($headerImageUrl = $post->headerImageUrl())
            <figure class="mx-auto mt-10 max-w-5xl">
                <img src="{{ $headerImageUrl }}" alt="{{ $post->localizedTitle() }}" class="aspect-[16/9] w-full rounded-[1.5rem] object-cover shadow-sm" decoding="async">
            </figure>
        @endif

        <div class="blog-article-content mx-auto mt-12 max-w-3xl">
            {{-- Sanitized on write in BlogPostController; rendered as HTML for the lightweight CMS body. --}}
            {!! $post->localizedBodyHtml() !!}
        </div>
    </article>
@endsection
