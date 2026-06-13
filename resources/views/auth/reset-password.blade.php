@extends('layouts.public')

@section('content')
    <section class="mx-auto max-w-xl px-6 py-20 lg:px-8">
        <div class="rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm">
            <p class="section-eyebrow">{{ __('site.reset_access_password') }}</p>
            <h1 class="mt-4 text-3xl font-semibold text-stone-950">{{ __('site.reset_access_password_title') }}</h1>
            <p class="mt-3 text-base leading-7 text-stone-600">{{ __('site.reset_access_password_intro') }}</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label class="form-label">{{ __('site.form_email') }}</label>
                    <input type="email" name="email" value="{{ old('email', $email) }}" class="form-input" autocomplete="email" required autofocus>
                    @error('email')
                        <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('site.form_password') }}</label>
                    <input type="password" name="password" class="form-input" autocomplete="new-password" required>
                    @error('password')
                        <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="form-label">{{ __('site.form_password_confirmation') }}</label>
                    <input type="password" name="password_confirmation" class="form-input" autocomplete="new-password" required>
                </div>

                <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">
                    {{ __('site.reset_access_password_submit') }}
                </button>
            </form>
        </div>
    </section>
@endsection
