@extends('layouts.panel', ['title' => $post->title, 'heading' => $post->title])

@section('content')
    @include('admin.blog.partials.form', ['action' => route('admin.blog.update', $post), 'method' => 'PUT', 'post' => $post])

    @php($translation = $post->englishTranslation)
    <section class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
        <h2 class="text-lg font-semibold text-stone-950">{{ __('site.english_translation') }}</h2>
        <p class="mt-2 text-sm leading-6 text-stone-600">{{ __('site.translation_review_note') }}</p>
        <form method="POST" action="{{ route('admin.blog.translations.en.update', $post) }}" class="mt-6 grid gap-5">
            @csrf
            @method('PUT')
            <div><label class="form-label">{{ __('site.form_title') }}</label><input name="title" value="{{ old('title', $translation?->title) }}" class="form-input" required></div>
            <div><label class="form-label">{{ __('site.form_summary') }}</label><textarea name="summary" rows="3" class="form-input" required>{{ old('summary', $translation?->summary) }}</textarea></div>
            <div><label class="form-label">{{ __('site.form_body_html') }}</label><textarea name="body_html" rows="12" class="form-input" required>{{ old('body_html', $translation?->body_html) }}</textarea></div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="form-label">{{ __('site.seo_title') }}</label><input name="seo_title" value="{{ old('seo_title', $translation?->seo_title) }}" class="form-input"></div>
                <div><label class="form-label">{{ __('site.seo_description') }}</label><input name="seo_description" value="{{ old('seo_description', $translation?->seo_description) }}" class="form-input"></div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-stone-700"><input type="checkbox" name="submit_for_review" value="1">{{ __('site.translation_submit_for_review') }}</label>
            <button type="submit" class="w-fit rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
        </form>
        @if (auth()->user()?->isSuperAdmin() && $translation?->status?->value === 'in_review')
            <form method="POST" action="{{ route('admin.blog.translations.en.approve', $post) }}" class="mt-4">@csrf<button class="rounded-full border border-olive-700 px-5 py-2.5 text-sm font-semibold text-olive-800">{{ __('site.approve_translation') }}</button></form>
        @endif
    </section>
@endsection
