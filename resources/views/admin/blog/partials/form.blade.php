<form method="POST" action="{{ $action }}" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5">
        <div>
            <label class="form-label">{{ __('site.form_title') }}</label>
            <input name="title" value="{{ old('title', $post->title) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_slug') }}</label>
            <input name="slug" value="{{ old('slug', $post->slug) }}" class="form-input">
        </div>
        <div>
            <label class="form-label">{{ __('site.form_summary') }}</label>
            <textarea name="summary" rows="3" class="form-input" required>{{ old('summary', $post->summary) }}</textarea>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_body_html') }}</label>
            <textarea name="body_html" rows="12" class="form-input" required>{{ old('body_html', $post->body_html) }}</textarea>
        </div>
        <div class="grid gap-5 md:grid-cols-3">
            <div>
                <label class="form-label">{{ __('site.form_status') }}</label>
                <select name="status" class="form-input" required>
                    <option value="draft" @selected(old('status', $post->status?->value) === 'draft')>{{ __('site.draft') }}</option>
                    <option value="published" @selected(old('status', $post->status?->value) === 'published')>{{ __('site.published') }}</option>
                </select>
            </div>
            <div>
                <label class="form-label">{{ __('site.form_published_at') }}</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($post->published_at)->format('Y-m-d\TH:i')) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">{{ __('site.form_keywords') }}</label>
                <input name="seo_keywords" value="{{ old('seo_keywords', is_array($post->seo_keywords) ? implode(', ', $post->seo_keywords) : '') }}" class="form-input">
            </div>
        </div>
    </div>

    <button type="submit" class="mt-6 rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
</form>
