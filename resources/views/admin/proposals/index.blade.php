@extends('layouts.panel', ['title' => __('site.admin_proposals'), 'heading' => __('site.admin_proposals')])

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-stone-500">{{ __('site.proposals_admin_intro') }}</p>
        <a href="{{ route('admin.proposals.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_proposal') }}</a>
    </div>

    <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-[980px] table-fixed text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="w-56 pb-3">{{ __('site.proposal_number') }}</th>
                        <th class="w-80 pb-3">{{ __('site.form_title') }}</th>
                        <th class="w-64 pb-3">{{ __('site.client_account') }}</th>
                        <th class="w-40 pb-3">{{ __('site.form_status') }}</th>
                        <th class="w-40 pb-3 text-right">{{ __('site.total') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($proposals as $proposal)
                        <tr class="transition hover:bg-stone-50/70">
                            <td class="py-4 pr-5">
                                <a class="inline-flex max-w-full cursor-pointer items-center justify-center truncate rounded-full bg-olive-50 px-4 py-2 text-sm font-semibold text-olive-800 ring-1 ring-olive-200 transition hover:bg-olive-100 hover:underline" href="{{ route('admin.proposals.show', $proposal) }}" title="{{ $proposal->proposal_number }}">
                                    {{ $proposal->proposal_number }}
                                </a>
                            </td>
                            <td class="truncate py-4 pr-5" title="{{ $proposal->title }}">{{ $proposal->title }}</td>
                            <td class="truncate py-4 pr-5" title="{{ $proposal->client?->name ?? __('site.unassigned') }}">{{ $proposal->client?->name ?? __('site.unassigned') }}</td>
                            <td class="py-4 pr-5">{{ $proposal->statusLabel() }}</td>
                            <td class="py-4 text-right font-semibold text-stone-900">{{ number_format((float) $proposal->total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">{{ $proposals->links() }}</div>
@endsection
