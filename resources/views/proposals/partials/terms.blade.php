<section class="rounded-2xl border border-olive-100 bg-olive-50/70 p-5">
    <h3 class="font-semibold text-stone-950">{{ __('site.proposal_terms_title') }}</h3>
    <div class="mt-3 space-y-3 text-base leading-7 text-stone-600">
        @foreach (__('site.proposal_terms_paragraphs') as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    </div>
</section>
