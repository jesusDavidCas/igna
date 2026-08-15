@extends('layouts.public')

@section('content')
    <section class="mx-auto max-w-7xl px-6 py-16 lg:px-8">
        <div class="max-w-3xl">
            <p class="section-eyebrow">{{ __('site.nav_blog') }}</p>
            <h1 class="section-title">{{ __('site.blog_archive_title') }}</h1>
            <p class="section-copy">{{ __('site.blog_archive_intro') }}</p>
        </div>

        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ($posts as $post)
                <article class="overflow-hidden rounded-[1.5rem] border border-stone-200 bg-white shadow-sm">
                    @if ($headerImageUrl = $post->headerImageUrl())
                        <img src="{{ $headerImageUrl }}" alt="{{ $post->localizedTitle() }}" class="aspect-[16/9] w-full object-cover" loading="lazy" decoding="async">
                    @endif
                    <div class="p-7">
                        <p class="text-xs uppercase tracking-[0.18em] text-stone-500">{{ optional($post->published_at)->format('Y-m-d') }}</p>
                        <h2 class="mt-3 text-xl font-semibold leading-snug text-stone-950">{{ $post->localizedTitle() }}</h2>
                        <p class="mt-4 text-sm leading-7 text-stone-600">{{ $post->localizedSummary() }}</p>
                        <a href="{{ route('blog.show', $post) }}" class="mt-6 inline-flex text-sm font-semibold text-olive-700">{{ __('site.read_article') }}</a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $posts->links() }}
        </div>
    </section>
@endsection
