# IGNA Studio Services

{{ __('site.services_intro') }}

## {{ __('site.services_engineering_title') }}

{{ __('site.services_engineering_intro') }}

@foreach ($services->where('business_line', 'engineering') as $service)
- {{ $service->localizedName() }}: {{ $service->localizedDescription() }}
@endforeach

## {{ __('site.services_digital_title') }}

{{ __('site.services_digital_intro') }}

@foreach ($services->where('business_line', 'digital') as $service)
- {{ $service->localizedName() }}: {{ $service->localizedDescription() }}
@endforeach
