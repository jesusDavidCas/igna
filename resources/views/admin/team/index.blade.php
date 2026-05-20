@extends('layouts.panel', ['title' => __('site.admin_team'), 'heading' => __('site.admin_team')])

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-stone-500">{{ __('site.team_admin_intro') }}</p>
        <a href="{{ route('admin.team.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_team_member') }}</a>
    </div>

    <div class="mt-8 grid gap-5 md:grid-cols-2">
        @foreach ($members as $member)
            <article class="rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-olive-700 text-sm font-semibold text-white">
                        @if ($member->photoUrl())
                            <img src="{{ $member->photoUrl() }}" alt="{{ $member->name }}" class="h-full w-full object-cover">
                        @else
                            {{ collect(explode(' ', $member->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
                        @endif
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-stone-950">{{ $member->name }}</h2>
                        <p class="text-sm text-stone-500">{{ $member->role }}</p>
                        <p class="mt-2 text-xs uppercase tracking-[0.18em] text-stone-400">{{ trans_choice('site.credential_count', $member->credentials_count, ['count' => $member->credentials_count]) }}</p>
                    </div>
                </div>
                <div class="mt-5 flex items-center gap-3">
                    <a href="{{ route('admin.team.edit', $member) }}" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700">{{ __('site.manage_team_member') }}</a>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $member->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-200 text-stone-600' }}">{{ $member->is_active ? __('site.active') : __('site.inactive') }}</span>
                </div>
            </article>
        @endforeach
    </div>
@endsection
