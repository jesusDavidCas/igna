<section class="section terms-section">
    <h2>{{ __('site.proposal_terms_title') }}</h2>
    @foreach (__('site.proposal_terms_paragraphs') as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
</section>
