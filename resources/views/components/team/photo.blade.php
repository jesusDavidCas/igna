@props(['member', 'variant' => 'card'])

@php
    $photoUrl = $member->photoUrl();
    $initials = $member->initials();
    $fallbackLabel = __('site.team_photo_fallback_label', ['name' => $member->name]);
    $frameClass = match ($variant) {
        'profile' => 'flex aspect-square w-full max-w-40 items-center justify-center overflow-hidden rounded-[1.6rem] bg-olive-700 text-3xl font-semibold text-white md:h-40 md:w-40',
        'avatar' => 'flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-olive-700 text-sm font-semibold text-white',
        default => 'expert-photo aspect-[4/5] overflow-hidden rounded-[1.3rem] bg-olive-700 text-white',
    };
    $imageClass = match ($variant) {
        'card' => 'h-full w-full object-cover transition duration-500 group-hover:scale-105',
        default => 'h-full w-full object-cover',
    };
    $fallbackClass = match ($variant) {
        'profile' => 'grid h-full w-full place-items-center text-3xl font-semibold',
        'avatar' => 'grid h-full w-full place-items-center text-sm font-semibold',
        default => 'grid h-full w-full place-items-center text-xl font-semibold',
    };
    $width = $variant === 'profile' ? 320 : ($variant === 'avatar' ? 128 : 304);
    $height = $variant === 'card' ? 380 : $width;
@endphp

<div data-team-photo-frame class="{{ $frameClass }}">
    @if ($photoUrl)
        <img
            src="{{ $photoUrl }}"
            alt="{{ $member->name }}"
            width="{{ $width }}"
            height="{{ $height }}"
            loading="lazy"
            decoding="async"
            class="{{ $imageClass }}"
        >
    @else
        <div role="img" aria-label="{{ $fallbackLabel }}" class="{{ $fallbackClass }}">
            {{ $initials }}
        </div>
    @endif
</div>
