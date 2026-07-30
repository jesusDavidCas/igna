@php
    $cleanValue = app(\App\Support\Proposals\ProposalContentSanitizer::class)->clean($value ?? '');
    $editorId = $id.'-editor';
    $toolbarId = $id.'-toolbar';
@endphp

<div data-rich-text-field data-warning-threshold="{{ $warningThreshold ?? 1200 }}" data-max-characters="{{ $maxCharacters ?? 10000 }}">
    <label id="{{ $id }}-label" for="{{ $editorId }}" class="form-label">{{ $label }}</label>
    <div id="{{ $toolbarId }}" class="mt-2 flex flex-wrap gap-2 rounded-t-2xl border border-b-0 border-stone-200 bg-stone-50 p-2" aria-label="{{ $label }}">
        <button type="button" data-rich-command="bold" class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700" aria-label="{{ __('site.rich_text_bold') }}">{{ __('site.rich_text_bold') }}</button>
        <button type="button" data-rich-command="italic" class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700" aria-label="{{ __('site.rich_text_italic') }}">{{ __('site.rich_text_italic') }}</button>
        <button type="button" data-rich-command="insertUnorderedList" class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700" aria-label="{{ __('site.rich_text_bulleted_list') }}">{{ __('site.rich_text_bulleted_list') }}</button>
        <button type="button" data-rich-command="insertOrderedList" class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700" aria-label="{{ __('site.rich_text_numbered_list') }}">{{ __('site.rich_text_numbered_list') }}</button>
        <button type="button" data-rich-command="removeFormat" class="rounded-full border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700" aria-label="{{ __('site.rich_text_clear_formatting') }}">{{ __('site.rich_text_clear_formatting') }}</button>
    </div>
    <div
        id="{{ $editorId }}"
        role="textbox"
        aria-labelledby="{{ $id }}-label"
        aria-multiline="true"
        aria-describedby="{{ $id }}-count {{ $errors->has($name) ? $errorId($name) : '' }}"
        contenteditable="true"
        data-rich-text-editor
        data-rich-text-target="{{ $id }}"
        data-placeholder="{{ __('site.rich_text_empty_state') }}"
        class="{{ $fieldClass($name) }} min-h-40 rounded-t-none leading-7"
        {!! $errorAttributes($name) !!}
    >{!! $cleanValue !!}</div>
    <textarea id="{{ $id }}" name="{{ $name }}" data-rich-text-input class="hidden">{{ $cleanValue }}</textarea>
    <div class="mt-2 flex flex-col gap-1 text-sm text-stone-500 sm:flex-row sm:items-center sm:justify-between">
        <p id="{{ $id }}-count" data-rich-character-count>{{ __('site.character_count') }}: <span>0</span> / {{ number_format($maxCharacters ?? 10000) }}</p>
        <p data-rich-long-warning class="hidden font-semibold text-amber-700">{{ __('site.long_description_pdf_warning') }} {{ __('site.complete_text_included') }}</p>
    </div>
    @if (! empty($help))
        <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ $help }}</p>
    @endif
    @error($name) <p id="{{ $errorId($name) }}" class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
</div>
