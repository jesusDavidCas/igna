@extends('layouts.panel', ['title' => $teamMember->name, 'heading' => $teamMember->name])

@section('content')
    @include('admin.team.partials.form', [
        'action' => route('admin.team.update', $teamMember),
        'method' => 'PUT',
        'teamMember' => $teamMember,
    ])

    @php($translation = $teamMember->englishTranslation)
    <section class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
        <h2 class="text-lg font-semibold text-stone-950">{{ __('site.english_translation') }}</h2>
        <p class="mt-2 text-sm leading-6 text-stone-600">{{ __('site.translation_review_note') }}</p>
        <form method="POST" action="{{ route('admin.team.translations.en.update', $teamMember) }}" class="mt-6 grid gap-5">
            @csrf
            @method('PUT')
            <div><label class="form-label">{{ __('site.form_role') }}</label><input name="role" value="{{ old('role', $translation?->role) }}" class="form-input" required></div>
            <div><label class="form-label">{{ __('site.short_description') }}</label><textarea name="short_description" rows="3" class="form-input" required>{{ old('short_description', $translation?->short_description) }}</textarea></div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="form-label">{{ __('site.bio') }}</label><textarea name="bio" rows="6" class="form-input" required>{{ old('bio', implode("\n", $translation?->bio ?? [])) }}</textarea></div>
                <div><label class="form-label">{{ __('site.expertise') }}</label><textarea name="expertise" rows="6" class="form-input" required>{{ old('expertise', implode("\n", $translation?->expertise ?? [])) }}</textarea></div>
            </div>
            <div class="grid gap-5 md:grid-cols-2">
                <div><label class="form-label">{{ __('site.seo_title') }}</label><input name="seo_title" value="{{ old('seo_title', $translation?->seo_title) }}" class="form-input"></div>
                <div><label class="form-label">{{ __('site.seo_description') }}</label><input name="seo_description" value="{{ old('seo_description', $translation?->seo_description) }}" class="form-input"></div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-stone-700"><input type="checkbox" name="submit_for_review" value="1">{{ __('site.translation_submit_for_review') }}</label>
            <button type="submit" class="w-fit rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
        </form>
        @if (auth()->user()?->isSuperAdmin() && $translation?->status?->value === 'in_review')
            <form method="POST" action="{{ route('admin.team.translations.en.approve', $teamMember) }}" class="mt-4">@csrf<button class="rounded-full border border-olive-700 px-5 py-2.5 text-sm font-semibold text-olive-800">{{ __('site.approve_translation') }}</button></form>
        @endif
    </section>

    <div class="mt-8 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <form method="POST" action="{{ route('admin.team.credentials.store', $teamMember) }}" enctype="multipart/form-data" class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.upload_credential') }}</h2>
            <div class="mt-5 space-y-4">
                <div>
                    <label class="form-label">{{ __('site.form_title') }}</label>
                    <input name="title" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">{{ __('site.institution') }}</label>
                    <input name="institution" class="form-input">
                </div>
                <div>
                    <label class="form-label">{{ __('site.issued_at') }}</label>
                    <input type="date" name="issued_at" class="form-input">
                </div>
                <div>
                    <label class="form-label">{{ __('site.display_order') }}</label>
                    <input type="number" name="sort_order" value="0" min="0" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_file') }}</label>
                    <input type="file" name="document" class="form-input" required>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                    <input type="checkbox" name="is_public" value="1" checked>
                    {{ __('site.list_publicly') }}
                </label>
                <button type="submit" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.upload_credential') }}</button>
            </div>
        </form>

        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.credentials') }}</h2>
            <div class="mt-5 space-y-4">
                @forelse ($teamMember->credentials as $credential)
                    @php($protectionState = $credential->protectionState())
                    <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="font-semibold text-stone-950">{{ $credential->title }}</p>
                                <p class="mt-1 text-sm text-stone-500">{{ $credential->original_name }}</p>
                                @if ($credential->institution || $credential->issued_at)
                                    <p class="mt-1 text-sm text-stone-500">{{ $credential->institution ?: '—' }} · {{ optional($credential->issued_at)->format('Y-m-d') ?: '—' }}</p>
                                @endif
                                <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-400">
                                    {{ $credential->is_public ? __('site.public_listing') : __('site.internal_only') }}
                                    · {{ trans_choice('site.view_count', $credential->views->count(), ['count' => $credential->views->count()]) }}
                                </p>
                                <p class="mt-2 text-xs font-semibold uppercase tracking-[0.18em] {{ in_array($protectionState, ['ready', 'ready_with_warning'], true) ? 'text-emerald-700' : 'text-amber-700' }}">
                                    @if ($protectionState === 'ready_with_warning')
                                        {{ __('site.credential_protection_ready_with_warning') }}
                                    @elseif ($protectionState === 'ready')
                                        {{ __('site.credential_protection_ready') }}
                                    @elseif ($protectionState === 'original_missing')
                                        {{ __('site.credential_original_missing') }}
                                    @elseif ($protectionState === 'generating')
                                        {{ __('site.credential_protection_generating') }}
                                    @else
                                        {{ __('site.credential_protection_failed') }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.team.credentials.regenerate', [$teamMember, $credential]) }}">
                                    @csrf
                                    <button class="rounded-full border border-stone-300 px-3 py-1 text-sm font-semibold text-stone-700">{{ __('site.regenerate_protected_credential') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.team.credentials.destroy', [$teamMember, $credential]) }}"
                                    data-confirm-title="{{ __('site.confirm_delete_credential_title') }}"
                                    data-confirm-message="{{ __('site.confirm_delete_credential_message') }}"
                                    data-confirm-danger="true">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded-full border border-rose-200 px-3 py-1 text-sm font-semibold text-rose-700">{{ __('site.delete') }}</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">{{ __('site.no_credentials') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8">
        @include('admin.partials.deletion-danger-zone', [
            'action' => route('admin.team.destroy', $teamMember),
            'entityKey' => 'team_member',
            'entityType' => __('site.deletion_type_team_member'),
            'identifier' => $teamMember->slug,
            'label' => $teamMember->name,
            'impact' => $deletionImpact,
        ])
    </div>
@endsection
