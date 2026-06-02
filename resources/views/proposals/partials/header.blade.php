@php
    $headingTag = $headingTag ?? 'h1';
@endphp

<header class="rounded-[1.75rem] border border-olive-100 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-olive-100 bg-white p-2 text-sm font-semibold text-olive-800 shadow-sm">
                @if (! empty($brand['logo_url']))
                    <img src="{{ $brand['logo_url'] }}" alt="{{ $brand['company_name'] }}" class="max-h-full max-w-full object-contain">
                @else
                    {{ $brand['logo_text'] }}
                @endif
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-olive-700">{{ __('site.quote_proposal') }}</p>
                <{{ $headingTag }} class="mt-2 max-w-3xl text-2xl font-semibold leading-tight text-stone-950 md:text-3xl">
                    {{ $proposal->title }}
                </{{ $headingTag }}>
                <p class="mt-2 max-w-3xl text-base leading-7 text-stone-600">{{ $proposal->subject }}</p>
            </div>
        </div>

        <div class="shrink-0 rounded-2xl border border-stone-200 bg-stone-50/80 px-4 py-3 text-left md:min-w-48 md:text-right">
            <p class="text-xs uppercase tracking-[0.18em] text-olive-700">{{ $brand['company_name'] }}</p>
            <p class="mt-1 text-sm font-semibold text-stone-950">{{ $proposal->proposal_number }}</p>
            @if ($proposal->issued_at)
                <p class="text-[15px] text-stone-500">{{ $proposal->issued_at->format('Y-m-d') }}</p>
            @endif
        </div>
    </div>
</header>
