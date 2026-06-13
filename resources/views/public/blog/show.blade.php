@extends('layouts.public')

@section('content')
    <article class="mx-auto max-w-4xl px-6 py-16 lg:px-8">
        <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-olive-700">{{ __('site.back_to_blog') }}</a>
        <h1 class="mt-6 text-4xl font-semibold text-stone-950">{{ $post->localizedTitle() }}</h1>
        <p class="mt-4 text-sm text-stone-500">{{ optional($post->published_at)->format('Y-m-d') }}</p>
        @if ($post->headerImageUrl())
            <img src="{{ $post->headerImageUrl() }}" alt="{{ $post->localizedTitle() }}" class="mt-8 w-full rounded-[2rem] object-cover shadow-sm">
        @endif
        <div class="prose prose-stone mt-10 max-w-none">
            {{-- Sanitized on write in BlogPostController; rendered as HTML for the lightweight CMS body. --}}
            {!! $post->localizedBodyHtml() !!}
        </div>
    </article>
@endsection
