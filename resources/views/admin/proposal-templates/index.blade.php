@extends('layouts.panel', ['title' => __('site.admin_proposal_templates'), 'heading' => __('site.admin_proposal_templates')])

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm text-stone-500">{{ __('site.proposal_templates_admin_intro') }}</p>
            <p class="mt-2 text-sm font-medium text-stone-600">{{ __('site.proposal_template_count', ['count' => $activeTemplates->count() + $inactiveTemplates->count()]) }}</p>
        </div>
        <a href="{{ route('admin.proposal-templates.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_proposal_template') }}</a>
    </div>

    @foreach ([
        __('site.active_templates') => $activeTemplates,
        __('site.inactive_templates') => $inactiveTemplates,
    ] as $sectionTitle => $templates)
        <section class="mt-8">
            <div class="border-b border-stone-200 pb-4">
                <h2 class="text-2xl font-semibold text-stone-950">{{ $sectionTitle }}</h2>
                <p class="mt-2 text-sm text-stone-500">{{ __('site.proposal_template_inactive_help') }}</p>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                @forelse ($templates as $template)
                    <article class="rounded-[1rem] border border-stone-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $template->code }}</p>
                                <h3 class="mt-3 text-xl font-semibold text-stone-950">{{ str_pad((string) $template->service_number, 2, '0', STR_PAD_LEFT) }} · {{ $template->localizedName() }}</h3>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-medium {{ $template->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-500' }}">
                                {{ $template->is_active ? __('site.active') : __('site.inactive') }}
                            </span>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-2 text-xs font-medium">
                            <span class="rounded-full bg-olive-50 px-3 py-1 text-olive-900">{{ __('site.proposal_template_item_count', ['count' => $template->items_count]) }}</span>
                            <span class="rounded-full bg-stone-100 px-3 py-1 text-stone-600">{{ __('site.proposal_template_sort_order') }}: {{ $template->sort_order }}</span>
                        </div>

                        <div class="mt-6 flex flex-wrap items-center gap-3">
                            <a href="{{ route('admin.proposal-templates.edit', $template) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.edit') }}</a>

                            <form method="POST" action="{{ route('admin.proposal-templates.duplicate', $template) }}" data-confirm-title="{{ __('site.duplicate_template_title') }}" data-confirm-message="{{ __('site.duplicate_template_message') }}">
                                @csrf
                                <button type="submit" class="rounded-full border border-olive-300 px-4 py-2 text-sm font-semibold text-olive-800">{{ __('site.duplicate_template') }}</button>
                            </form>

                            <form method="POST" action="{{ route('admin.proposal-templates.status', $template) }}" data-confirm-title="{{ $template->is_active ? __('site.confirm_deactivate_template_title') : __('site.confirm_activate_template_title') }}" data-confirm-message="{{ $template->is_active ? __('site.confirm_deactivate_template_message') : __('site.confirm_activate_template_message') }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $template->is_active ? '0' : '1' }}">
                                <button type="submit" class="rounded-full border {{ $template->is_active ? 'border-rose-200 text-rose-700' : 'border-emerald-200 text-emerald-700' }} px-4 py-2 text-sm font-semibold">
                                    {{ $template->is_active ? __('site.deactivate_template') : __('site.activate_template') }}
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1rem] border border-dashed border-stone-300 bg-stone-50 p-8 text-sm text-stone-500 lg:col-span-2">
                        {{ __('site.proposal_template_count', ['count' => 0]) }}
                    </div>
                @endforelse
            </div>
        </section>
    @endforeach
@endsection
