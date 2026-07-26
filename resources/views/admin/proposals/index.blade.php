@extends('layouts.panel', ['title' => __('site.admin_proposals'), 'heading' => __('site.admin_proposals')])

@section('content')
    @php
        $createdDirection = $direction === 'desc' ? 'asc' : 'desc';
        $sortLabel = $direction === 'desc' ? __('site.sort_oldest_first') : __('site.sort_newest_first');
        $sortUrl = route('admin.proposals.index', array_filter([
            'search' => $search ?: null,
            'status' => $status ?: null,
            'sort' => 'created_at',
            'direction' => $createdDirection,
        ]));
    @endphp

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-sm text-stone-500">{{ __('site.proposals_admin_intro') }}</p>
            <form method="GET" action="{{ route('admin.proposals.index') }}" class="mt-4 grid gap-3 md:grid-cols-[1fr_0.5fr_auto]">
                <input type="hidden" name="sort" value="created_at">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <div>
                    <label for="proposal-search" class="sr-only">{{ __('site.search') }}</label>
                    <input id="proposal-search" name="search" value="{{ $search }}" class="form-input bg-white" placeholder="{{ __('site.search') }}">
                </div>
                <div>
                    <label for="proposal-status-filter" class="sr-only">{{ __('site.form_status') }}</label>
                    <select id="proposal-status-filter" name="status" class="form-input bg-white">
                        <option value="">{{ __('site.all_statuses') }}</option>
                        @foreach (['draft', 'sent', 'approved', 'rejected'] as $statusOption)
                            <option value="{{ $statusOption }}" @selected($status === $statusOption)>{{ __("site.proposal_status_{$statusOption}") }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-full bg-stone-950 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.apply_filters') }}</button>
            </form>
        </div>
        <a href="{{ route('admin.proposals.create') }}" class="inline-flex items-center justify-center rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_proposal') }}</a>
    </div>

    <div class="mt-8 space-y-4 md:hidden">
        @forelse ($proposals as $proposal)
            <article class="rounded-[1.25rem] border border-stone-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3">
                    <a class="inline-flex max-w-full items-center justify-center self-start rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200" href="{{ route('admin.proposals.show', $proposal) }}" title="{{ $proposal->proposal_number }}">
                        {{ $proposal->proposal_number }}
                    </a>
                    <div class="min-w-0">
                        <h2 class="break-words font-semibold text-stone-950">{{ $proposal->title }}</h2>
                        <p class="mt-1 break-words text-sm text-stone-500">{{ $proposal->clientDisplayName() }}</p>
                    </div>
                    <dl class="grid gap-3 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-stone-500">{{ __('site.form_status') }}</dt>
                            <dd class="font-semibold text-stone-900">{{ $proposal->statusLabel() }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-stone-500">{{ __('site.created_at') }}</dt>
                            <dd class="font-semibold text-stone-900">{{ $proposal->created_at?->translatedFormat('Y-m-d H:i') }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-stone-500">{{ __('site.total') }}</dt>
                            <dd class="font-semibold text-stone-900">{{ number_format((float) $proposal->total, 2) }}</dd>
                        </div>
                    </dl>
                </div>
            </article>
        @empty
            <p class="rounded-[1.25rem] border border-stone-200 bg-white p-5 text-center text-stone-500">{{ __('site.no_proposals_yet') }}</p>
        @endforelse
    </div>

    <div class="mt-8 hidden rounded-[1.5rem] border border-stone-200 bg-white p-6 shadow-sm md:block">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] table-fixed text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="w-52 pb-3">{{ __('site.proposal_number') }}</th>
                        <th class="w-72 pb-3">{{ __('site.form_title') }}</th>
                        <th class="w-56 pb-3">{{ __('site.client') }}</th>
                        <th class="w-40 pb-3">{{ __('site.form_status') }}</th>
                        <th class="w-36 pb-3">
                            <a href="{{ $sortUrl }}" class="inline-flex items-center gap-1.5 whitespace-nowrap font-semibold text-stone-700 hover:text-olive-800" aria-label="{{ $sortLabel }}" title="{{ $sortLabel }}" aria-sort="{{ $direction === 'desc' ? 'descending' : 'ascending' }}">
                                {{ __('site.created_at') }}
                                <span aria-hidden="true" class="text-base leading-none">{{ $direction === 'desc' ? '↓' : '↑' }}</span>
                            </a>
                        </th>
                        <th class="w-40 pb-3 text-right">{{ __('site.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @forelse ($proposals as $proposal)
                        <tr class="transition hover:bg-stone-50/70">
                            <td class="py-4 pr-5">
                                <a class="inline-flex max-w-full cursor-pointer items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100 hover:underline" href="{{ route('admin.proposals.show', $proposal) }}" title="{{ $proposal->proposal_number }}">
                                    {{ $proposal->proposal_number }}
                                </a>
                            </td>
                            <td class="truncate py-4 pr-5" title="{{ $proposal->title }}">{{ $proposal->title }}</td>
                            <td class="truncate py-4 pr-5" title="{{ $proposal->clientDisplayName() }}">{{ $proposal->clientDisplayName() }}</td>
                            <td class="py-4 pr-5">{{ $proposal->statusLabel() }}</td>
                            <td class="py-4 pr-5">{{ $proposal->created_at?->translatedFormat('Y-m-d H:i') }}</td>
                            <td class="py-4 text-right font-semibold text-stone-900">{{ number_format((float) $proposal->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-stone-500">{{ __('site.no_proposals_yet') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">{{ $proposals->links() }}</div>
@endsection
