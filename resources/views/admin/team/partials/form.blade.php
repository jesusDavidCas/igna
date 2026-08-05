<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label class="form-label">{{ __('site.form_name') }}</label>
            <input name="name" value="{{ old('name', $teamMember->name) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_slug') }}</label>
            <input name="slug" value="{{ old('slug', $teamMember->slug) }}" class="form-input">
        </div>
        <div>
            <label class="form-label">{{ __('site.form_role') }}</label>
            <input name="role" value="{{ old('role', $teamMember->role) }}" class="form-input" required>
        </div>
        <div>
            <label class="form-label">{{ __('site.form_sort_order') }}</label>
            <input type="number" name="sort_order" value="{{ old('sort_order', $teamMember->sort_order ?? 0) }}" min="0" class="form-input" required>
        </div>
        <div class="md:col-span-2">
            <label class="form-label">{{ __('site.short_description') }}</label>
            <textarea name="short_description" rows="3" class="form-input">{{ old('short_description', $teamMember->short_description) }}</textarea>
        </div>
        <div>
            <label class="form-label">{{ __('site.bio') }}</label>
            <textarea name="bio" rows="6" class="form-input">{{ old('bio', implode("\n", $teamMember->bio ?? [])) }}</textarea>
            <p class="mt-2 text-xs text-stone-500">{{ __('site.one_item_per_line') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('site.expertise') }}</label>
            <textarea name="expertise" rows="6" class="form-input">{{ old('expertise', implode("\n", $teamMember->expertise ?? [])) }}</textarea>
            <p class="mt-2 text-xs text-stone-500">{{ __('site.one_item_per_line') }}</p>
        </div>
        <div>
            <label class="form-label">{{ __('site.profile_photo') }}</label>
            <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="form-input">
            <p class="mt-2 text-xs text-stone-500">{{ __('site.profile_photo_help') }}</p>
        </div>
        <div class="flex items-end">
            <label class="inline-flex items-center gap-2 text-sm text-stone-700">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $teamMember->is_active ?? true))>
                {{ __('site.active') }}
            </label>
        </div>
    </div>

    <button type="submit" class="mt-6 rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.save_changes') }}</button>
</form>
