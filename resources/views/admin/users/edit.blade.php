@extends('layouts.panel', ['title' => $user->name, 'heading' => $user->name])

@section('content')
    @include('admin.users.partials.form', [
        'action' => route('admin.users.update', $user),
        'method' => 'PUT',
        'user' => $user,
        'roles' => $roles,
    ])

    <form method="POST" action="{{ route('admin.users.password.update', $user) }}" class="mt-8 rounded-[2rem] border border-amber-200 bg-amber-50/70 p-8 shadow-sm">
        @csrf
        @method('PUT')

        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-amber-700">{{ __('site.reset_password') }}</p>
        <h2 class="mt-3 text-2xl font-semibold text-stone-950">{{ __('site.reset_password_title') }}</h2>
        <p class="mt-2 max-w-2xl text-sm leading-7 text-stone-600">{{ __('site.reset_password_help') }}</p>

        <div class="mt-6 grid gap-5 md:grid-cols-2">
            <div>
                <label class="form-label">{{ __('site.form_password') }}</label>
                <input type="password" name="password" class="form-input" required autocomplete="new-password">
            </div>
            <div>
                <label class="form-label">{{ __('site.form_password_confirmation') }}</label>
                <input type="password" name="password_confirmation" class="form-input" required autocomplete="new-password">
            </div>
        </div>

        <button type="submit" class="mt-6 rounded-full bg-stone-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-stone-800">
            {{ __('site.reset_password_submit') }}
        </button>
    </form>

    <div class="mt-8">
        @include('admin.partials.deletion-danger-zone', [
            'action' => route('admin.users.destroy', $user),
            'entityKey' => 'user',
            'entityType' => __('site.deletion_type_user'),
            'identifier' => $user->email,
            'label' => $user->name,
            'impact' => $deletionImpact,
            'blockedMessage' => $user->is(auth()->user()) ? __('site.user_delete_current_account_blocked') : null,
        ])
    </div>
@endsection
