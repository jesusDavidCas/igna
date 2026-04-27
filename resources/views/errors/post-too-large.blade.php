<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $message }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-100 text-stone-900">
        <main class="flex min-h-screen items-center justify-center px-6">
            <section class="max-w-xl rounded-[2rem] border border-rose-200 bg-white p-8 text-center shadow-sm">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-rose-600">413</p>
                <h1 class="mt-4 text-2xl font-semibold text-stone-950">{{ $message }}</h1>
                <button type="button" onclick="history.back()" class="mt-6 rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">
                    {{ __('site.go_back') }}
                </button>
            </section>
        </main>
    </body>
</html>
