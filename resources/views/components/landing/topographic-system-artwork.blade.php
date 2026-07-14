<div class="hero-system-artwork" role="img" aria-label="{{ __('site.hero_map_label') }}">
    <picture>
        <source media="(max-width: 639px)" srcset="{{ asset('images/landing/topographic-system-mobile.webp') }}" type="image/webp">
        <source media="(max-width: 1023px)" srcset="{{ asset('images/landing/topographic-system-tablet.webp') }}" type="image/webp">
        <source srcset="{{ asset('images/landing/topographic-system-desktop.webp') }}" type="image/webp">
        <img
            src="{{ asset('images/landing/topographic-system-desktop.png') }}"
            alt=""
            width="1586"
            height="800"
            fetchpriority="high"
            decoding="async"
        >
    </picture>

    {{-- Labels stay live so locale changes never require regenerating the artwork. --}}
    <div class="hero-system-labels" aria-hidden="true">
        <span class="hero-map-label hero-map-label--intake">
            <b>{{ __('site.hero_map_capture') }}</b>
            <small>{{ __('site.hero_map_elevation') }}</small>
        </span>
        <span class="hero-map-label hero-map-label--treatment">
            <b>{{ __('site.hero_map_plant') }}</b>
            <small>{{ __('site.hero_map_elevation_lower') }}</small>
        </span>
        <span class="hero-map-label hero-map-label--reservoir">
            <b>{{ __('site.hero_map_reservoir') }}</b>
            <small>{{ __('site.hero_map_elevation_reservoir') }}</small>
        </span>
        <span class="hero-map-label hero-map-label--distribution">{{ __('site.hero_map_distribution') }}</span>
        <span class="hero-map-axis hero-map-axis--engineering">{{ __('site.hero_technical_engineering') }}</span>
        <span class="hero-map-axis hero-map-axis--data">{{ __('site.hero_technical_data') }}</span>
        <span class="hero-map-axis hero-map-axis--territory">{{ __('site.hero_technical_territory') }}</span>
        <span class="hero-map-north">N</span>
    </div>
</div>
