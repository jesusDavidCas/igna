@extends('layouts.panel', ['title' => $service->localizedName(), 'heading' => $service->localizedName()])

@section('content')
    @php
        $contentLocale = app()->getLocale() === 'es' ? 'es' : 'en';
        $targetLocale = $contentLocale === 'es' ? 'en' : 'es';
    @endphp

    @include('admin.services.partials.form', [
        'action' => route('admin.services.update', $service),
        'method' => 'PUT',
        'service' => $service,
        'serviceTypes' => $serviceTypes,
        'serviceScopes' => $serviceScopes,
        'deletionImpact' => $deletionImpact,
    ])

    <div class="mt-8 grid gap-6 lg:grid-cols-[0.6fr_0.4fr]">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.stage_workflow') }}</h2>
            <div class="mt-6 space-y-3">
                @foreach ($service->stages as $stage)
                    <details class="rounded-2xl border border-stone-200 bg-stone-50 p-4" @if((int) old('editing_stage_id') === $stage->id || ($errors->hasAny(['name_en', 'name_es', 'code', 'description_en', 'description_es', 'sort_order']) && ! old('editing_stage_id'))) open @endif>
                        <summary class="cursor-pointer list-none">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ str_pad((string) $stage->sort_order, 2, '0', STR_PAD_LEFT) }} · {{ $stage->code }}</p>
                                    <h3 class="mt-1 truncate text-base font-semibold text-stone-950">{{ $stage->localizedName() }}</h3>
                                    <p class="mt-1 line-clamp-2 text-sm text-stone-500">{{ $stage->localizedDescription() ?: '-' }}</p>
                                </div>
                                <span class="text-sm font-semibold text-olive-700">{{ __('site.edit_stage') }}</span>
                            </div>
                        </summary>

                        <form method="POST" action="{{ route('admin.services.stages.update', [$service, $stage]) }}" class="mt-5">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="editing_stage_id" value="{{ $stage->id }}">
                            <input type="hidden" name="content_locale" value="{{ $contentLocale }}">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="md:col-span-2">
                                    <label class="form-label">{{ __('site.form_name') }}</label>
                                    <input name="name" value="{{ old('name', old("name_{$contentLocale}", $contentLocale === 'es' ? ($stage->name_es ?: $stage->localizedName()) : ($stage->name_en ?: $stage->localizedName()))) }}" class="form-input" required>
                                    <input type="hidden" name="name_{{ $targetLocale }}" value="{{ old("name_{$targetLocale}", $targetLocale === 'es' ? $stage->name_es : $stage->name_en) }}">
                                </div>
                                <div>
                                    <label class="form-label">{{ __('site.form_code') }}</label>
                                    <input name="code" value="{{ old('code', $stage->code) }}" class="form-input" required>
                                </div>
                                <div>
                                    <label class="form-label">{{ __('site.form_sort_order') }}</label>
                                    <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $stage->sort_order) }}" class="form-input" required>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="form-label">{{ __('site.form_description') }}</label>
                                    <textarea name="description" rows="2" class="form-input">{{ old('description', old("description_{$contentLocale}", $contentLocale === 'es' ? ($stage->description_es ?: $stage->localizedDescription()) : ($stage->description_en ?: $stage->localizedDescription()))) }}</textarea>
                                    <input type="hidden" name="description_{{ $targetLocale }}" value="{{ old("description_{$targetLocale}", $targetLocale === 'es' ? $stage->description_es : $stage->description_en) }}">
                                </div>
                                <div class="flex items-end gap-5 md:col-span-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                                        <input type="checkbox" name="is_active" value="1" @checked($stage->is_active)>
                                        {{ __('site.active') }}
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                                        <input type="checkbox" name="is_client_visible" value="1" @checked($stage->is_client_visible)>
                                        {{ __('site.client_visible') }}
                                    </label>
                                </div>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <button type="submit" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.save_stage') }}</button>
                                <p class="text-sm leading-6 text-stone-500">{{ __('site.dynamic_translation_cache_note') }}</p>
                            </div>
                        </form>
                        <form method="POST" action="{{ route('admin.services.stages.destroy', [$service, $stage]) }}" class="mt-3">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.remove_stage') }}</button>
                        </form>
                    </details>
                @endforeach
            </div>
        </div>

        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.add_stage') }}</h2>
            <form method="POST" action="{{ route('admin.services.stages.store', $service) }}" class="mt-6 space-y-4">
                @csrf
                <input type="hidden" name="content_locale" value="{{ $contentLocale }}">
                <div>
                    <label class="form-label">{{ __('site.form_name') }}</label>
                    <input name="name" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_code') }}</label>
                    <input name="code" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_description') }}</label>
                    <textarea name="description" rows="3" class="form-input"></textarea>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_sort_order') }}</label>
                    <input type="number" min="1" name="sort_order" value="{{ ($service->stages->max('sort_order') ?? 0) + 1 }}" class="form-input" required>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                    <input type="checkbox" name="is_active" value="1" checked>
                    {{ __('site.active') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                    <input type="checkbox" name="is_client_visible" value="1" checked>
                    {{ __('site.client_visible') }}
                </label>
                <button type="submit" class="block rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.add_stage') }}</button>
            </form>
        </div>
    </div>

    @if (auth()->user()->isSuperAdmin() && $deletionImpact->canDelete())
        <x-admin.delete-confirmation-modal
            id="delete-service-{{ $service->id }}"
            :action="route('admin.services.destroy', $service)"
            :title="__('site.deletion_modal_title_service')"
            :question="__('site.deletion_modal_question_service')"
            :identifier="$service->code"
            :consequence="__('site.deletion_modal_consequence_service')"
        />
    @endif
@endsection
