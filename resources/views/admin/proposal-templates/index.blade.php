@extends('layouts.panel', ['title' => __('site.admin_proposal_templates'), 'heading' => __('site.admin_proposal_templates')])

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm text-stone-500">{{ __('site.proposal_templates_admin_intro') }}</p>
        </div>
        <a href="{{ route('admin.proposal-templates.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_proposal_template') }}</a>
    </div>

    <section class="mt-8">
        <div class="border-b border-stone-200 pb-4">
            <h2 class="text-2xl font-semibold text-stone-950">{{ __('site.proposal_template_catalogue') }}</h2>
            <p class="mt-2 text-sm text-stone-500">{{ __('site.proposal_template_delete_help') }}</p>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @forelse ($templates as $template)
                <article class="rounded-[1rem] border border-stone-200 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $template->code }}</p>
                            <h3 class="mt-3 text-xl font-semibold text-stone-950">{{ $template->localizedName() }}</h3>
                        </div>
                        @unless ($template->is_active)
                            <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-500">
                                {{ __('site.legacy_inactive_template') }}
                            </span>
                        @endunless
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ route('admin.proposal-templates.edit', $template) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.edit') }}</a>

                        <form method="POST" action="{{ route('admin.proposal-templates.duplicate', $template) }}" data-confirm-title="{{ __('site.duplicate_template_title') }}" data-confirm-message="{{ __('site.duplicate_template_message') }}">
                            @csrf
                            <button type="submit" class="rounded-full border border-olive-300 px-4 py-2 text-sm font-semibold text-olive-800">{{ __('site.duplicate_template') }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.proposal-templates.destroy', $template) }}" data-confirm-title="{{ __('site.confirm_delete_template_title') }}" data-confirm-message="{{ __('site.confirm_delete_template_message', ['template' => $template->localizedName(), 'code' => $template->code]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-700">{{ __('site.delete') }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-[1rem] border border-dashed border-stone-300 bg-stone-50 p-8 text-sm text-stone-500 lg:col-span-2">
                    {{ __('site.no_proposal_templates') }}
                </div>
            @endforelse
        </div>
    </section>
@endsection
