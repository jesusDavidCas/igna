@extends('layouts.panel', ['title' => __('site.admin_services'), 'heading' => __('site.admin_services')])

@section('content')
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <p class="text-sm text-stone-500">{{ __('site.services_admin_intro') }}</p>
        </div>
        <a href="{{ route('admin.services.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_service') }}</a>
    </div>

    <div class="mt-8 space-y-10">
        @foreach (['digital' => __('site.business_line_digital'), 'engineering' => __('site.business_line_engineering')] as $line => $label)
            <section>
                <div class="flex items-end justify-between gap-4 border-b border-stone-200 pb-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-olive-700">{{ __('site.service_line') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold text-stone-950">{{ $label }}</h2>
                    </div>
                    <p class="text-sm text-stone-500">{{ __('site.service_count', ['count' => $servicesByLine->get($line, collect())->count()]) }}</p>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @forelse ($servicesByLine->get($line, collect()) as $service)
                        <article class="rounded-[1rem] border border-stone-200 bg-white p-6 shadow-sm">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $service->code }}</p>
                                    <h3 class="mt-3 text-xl font-semibold text-stone-950">{{ $service->localizedName() }}</h3>
                                </div>
                                <span class="rounded-full px-3 py-1 text-xs font-medium {{ $service->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-500' }}">
                                    {{ $service->is_active ? __('site.active') : __('site.inactive') }}
                                </span>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span class="rounded-full bg-olive-50 px-3 py-1 text-xs font-medium text-olive-900">{{ $serviceTypeLabels[$service->service_type] ?? str($service->service_type)->headline() }}</span>
                                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600">{{ $serviceScopeLabels[$service->service_scope] ?? str($service->service_scope)->headline() }}</span>
                                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-stone-600">{{ __('site.stage_count', ['count' => $service->stages->count()]) }}</span>
                            </div>

                            <p class="mt-4 text-sm leading-7 text-stone-600">{{ $service->localizedDescription() }}</p>

                            @if (! empty($service->localizedDeliverables()))
                                <div class="mt-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.deliverables') }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach (array_slice($service->localizedDeliverables(), 0, 4) as $deliverable)
                                            <span class="rounded-full bg-stone-50 px-3 py-1 text-xs text-stone-600">{{ $deliverable }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <a href="{{ route('admin.services.edit', $service) }}" class="mt-6 inline-flex text-sm font-semibold text-olive-700">{{ __('site.manage_service') }}</a>
                        </article>
                    @empty
                        <div class="rounded-[1rem] border border-dashed border-stone-300 bg-stone-50 p-8 text-sm text-stone-500 lg:col-span-2">
                            {{ __('site.no_services_in_line') }}
                        </div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
@endsection
