@extends('layouts.panel', ['title' => $service->localizedName(), 'heading' => $service->localizedName()])

@section('content')
    @include('admin.services.partials.form', [
        'action' => route('admin.services.update', $service),
        'method' => 'PUT',
        'service' => $service,
        'serviceTypes' => $serviceTypes,
        'serviceScopes' => $serviceScopes,
    ])

    <div class="mt-8 grid gap-6 lg:grid-cols-[0.55fr_0.45fr]">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.stage_workflow') }}</h2>
            <div class="mt-6 space-y-4">
                @foreach ($service->stages as $stage)
                    <form method="POST" action="{{ route('admin.services.stages.update', [$service, $stage]) }}" class="rounded-2xl border border-stone-200 bg-stone-50 p-5">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="form-label">{{ __('site.form_name') }}</label>
                                <input name="name" value="{{ old('name', $stage->name) }}" class="form-input" required>
                            </div>
                            <div>
                                <label class="form-label">{{ __('site.form_code') }}</label>
                                <input name="code" value="{{ old('code', $stage->code) }}" class="form-input" required>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label">{{ __('site.form_description') }}</label>
                                <textarea name="description" rows="3" class="form-input">{{ old('description', $stage->description) }}</textarea>
                            </div>
                            <div>
                                <label class="form-label">{{ __('site.form_sort_order') }}</label>
                                <input type="number" min="1" name="sort_order" value="{{ old('sort_order', $stage->sort_order) }}" class="form-input" required>
                            </div>
                            <div class="flex items-end gap-5">
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
                        <button type="submit" class="mt-4 rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.save_stage') }}</button>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-stone-950">{{ __('site.add_stage') }}</h2>
            <form method="POST" action="{{ route('admin.services.stages.store', $service) }}" class="mt-6 space-y-4">
                @csrf
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
                    <textarea name="description" rows="4" class="form-input"></textarea>
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
@endsection
