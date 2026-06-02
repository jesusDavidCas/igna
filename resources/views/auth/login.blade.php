@extends('layouts.public', ['title' => __('site.nav_login')])

@section('content')
    <section class="mx-auto max-w-xl px-6 py-20 lg:px-8">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="section-eyebrow">{{ __('site.nav_login') }}</p>
            <h1 class="mt-4 text-3xl font-semibold text-stone-950">{{ __('site.login_title') }}</h1>
            <p class="mt-3 text-sm leading-7 text-stone-600">{{ __('site.login_intro') }}</p>

            @if (session('status'))
                <div class="mt-6 rounded-2xl border border-olive-200 bg-olive-50 px-4 py-3 text-sm font-semibold text-olive-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label class="form-label">{{ __('site.form_email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_password') }}</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">
                        {{ __('site.login_submit') }}
                    </button>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-olive-800 underline decoration-olive-300 underline-offset-4 transition hover:text-olive-950">
                        {{ __('site.forgot_password') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
