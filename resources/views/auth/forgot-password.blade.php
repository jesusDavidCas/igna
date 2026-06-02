@extends('layouts.public', ['title' => __('site.forgot_password')])

@section('content')
    <section class="mx-auto max-w-xl px-6 py-20 lg:px-8">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="section-eyebrow">{{ __('site.forgot_password') }}</p>
            <h1 class="mt-4 text-3xl font-semibold text-stone-950">{{ __('site.forgot_password_title') }}</h1>
            <p class="mt-3 text-base leading-7 text-stone-600">{{ __('site.forgot_password_intro') }}</p>

            @if (session('status'))
                <div class="mt-6 rounded-2xl border border-olive-200 bg-olive-50 px-4 py-3 text-sm font-semibold text-olive-800">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
                @csrf
                <div>
                    <label class="form-label">{{ __('site.form_email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-input" autocomplete="email" required autofocus>
                    @error('email')
                        <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">
                        {{ __('site.send_reset_link') }}
                    </button>
                    <a href="{{ route('login') }}" class="rounded-full border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-700 transition hover:border-olive-600 hover:text-olive-800">
                        {{ __('site.back_to_login') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
