@extends('layouts.public')

@section('content')
    @php
        $process = [
            __('site.process_step_1'),
            __('site.process_step_2'),
            __('site.process_step_3'),
            __('site.process_step_4'),
        ];

        $projects = [
            ['name' => __('site.project_name_1'), 'type' => __('site.project_type_digital'), 'description' => __('site.project_sample_1')],
            ['name' => __('site.project_name_2'), 'type' => __('site.project_type_infrastructure'), 'description' => __('site.project_sample_2')],
            ['name' => __('site.project_name_3'), 'type' => __('site.project_type_digital'), 'description' => __('site.project_sample_3')],
        ];

        $serviceGroups = collect([
            'engineering' => [
                'eyebrow' => __('site.services_engineering_eyebrow'),
                'title' => __('site.services_engineering_title'),
                'intro' => __('site.services_engineering_intro'),
                'index' => 'A',
                'class' => 'territory-water',
            ],
            'digital' => [
                'eyebrow' => __('site.services_digital_eyebrow'),
                'title' => __('site.services_digital_title'),
                'intro' => __('site.services_digital_intro'),
                'index' => 'B',
                'class' => 'territory-digital',
            ],
        ]);
    @endphp

    <div class="landing-canvas">
        <section class="selected-hero selected-hero--with-overlay-header relative isolate overflow-hidden border-b border-olive-200/80">
            <div class="site-shell selected-hero-grid relative grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:gap-10">
                <div class="hero-system-copy relative z-10 max-w-3xl lg:pb-10">
                    <p class="section-kicker">{{ __('site.hero_eyebrow') }}</p>
                    <h1 class="mt-5 max-w-xl text-[2.6rem] font-normal leading-[1.03] tracking-[-0.045em] text-olive-800 sm:text-6xl lg:text-[clamp(3.5rem,4.35vw,4.35rem)]">
                        {{ __('site.hero_title') }}
                    </h1>
                    <span class="hero-clay-rule mt-7" aria-hidden="true"></span>
                    <p class="mt-7 max-w-[39ch] text-[17px] leading-7 text-stone-600 sm:text-lg">
                        {{ __('site.hero_description') }}
                    </p>
                    <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-4">
                        <a href="#request" class="hero-text-action">
                            {{ __('site.cta_request') }}
                            <span aria-hidden="true">&#8594;</span>
                        </a>
                        <a href="{{ route('tracking.index') }}" class="border-b border-olive-700 pb-1 text-sm font-semibold text-olive-800 transition hover:text-olive-700">
                            {{ __('site.cta_track') }}
                        </a>
                    </div>
                </div>

                <x-landing.topographic-system-artwork />
            </div>
        </section>

        <section class="service-band">
            <div class="site-shell service-band-grid">
                <div class="service-band-intro">
                    <p class="section-kicker text-olive-200">{{ __('site.services_band_eyebrow') }}</p>
                    <p class="mt-5 max-w-md text-3xl font-medium leading-tight tracking-[-0.03em] text-white sm:text-4xl">{{ __('site.services_band_title') }}</p>
                </div>
                <a href="#services-engineering" class="service-band-link">
                    <span class="service-band-icon" aria-hidden="true">
                        <svg viewBox="0 0 40 40" fill="none"><path d="M4 11c8-7 16 6 24-1 4-3 6-4 8-3M4 18c8-7 16 6 24-1 4-3 6-4 8-3M4 25c8-7 16 6 24-1 4-3 6-4 8-3M4 32c8-7 16 6 24-1 4-3 6-4 8-3"/></svg>
                    </span>
                    <span class="service-band-title">{{ __('site.services_band_water') }}</span>
                    <b aria-hidden="true">&#8594;</b>
                </a>
                <a href="#services-engineering" class="service-band-link">
                    <span class="service-band-icon service-band-icon-network" aria-hidden="true">
                        <svg viewBox="0 0 40 40" fill="none"><path d="M10 12 20 20l10-8M10 28l10-8 10 8M10 12v16M30 12v16"/><circle cx="10" cy="12" r="4"/><circle cx="30" cy="12" r="4"/><circle cx="20" cy="20" r="4"/><circle cx="10" cy="28" r="4"/><circle cx="30" cy="28" r="4"/></svg>
                    </span>
                    <span class="service-band-title">{{ __('site.services_band_engineering') }}</span>
                    <b aria-hidden="true">&#8594;</b>
                </a>
                <a href="#services-digital" class="service-band-link">
                    <span class="service-band-icon service-band-icon-drop" aria-hidden="true">
                        <svg viewBox="0 0 40 40" fill="none"><path d="M20 4c7 9 12 15 12 21a12 12 0 1 1-24 0c0-6 5-12 12-21Z"/></svg>
                    </span>
                    <span class="service-band-title">{{ __('site.services_band_digital') }}</span>
                    <b aria-hidden="true">&#8594;</b>
                </a>
            </div>
        </section>

        <section id="services" class="relative py-24 lg:py-32">
            <div class="site-shell">
                <div class="max-w-4xl">
                    <p class="section-kicker">{{ __('site.nav_services') }}</p>
                    <h2 class="section-title">{{ __('site.services_title') }}</h2>
                    <p class="section-copy">{{ __('site.services_intro') }}</p>
                </div>

                <div class="mt-14 space-y-16 lg:mt-18">
                    @foreach ($serviceGroups as $line => $group)
                        @php($lineServices = $services->where('business_line', $line))

                        @if ($lineServices->isNotEmpty())
                            <section id="services-{{ $line }}" class="territory {{ $group['class'] }}">
                                <div class="territory-rail" aria-hidden="true"></div>
                                <div class="grid lg:grid-cols-[0.76fr_1.24fr]">
                                    <aside class="territory-copy">
                                        <p class="section-kicker">{{ $group['eyebrow'] }}</p>
                                        <h3 class="mt-5 max-w-xl text-3xl font-semibold leading-[1.06] tracking-[-0.03em] text-igna-charcoal lg:text-4xl">{{ $group['title'] }}</h3>
                                        <p class="mt-6 max-w-lg text-[17px] leading-8 text-stone-600">{{ $group['intro'] }}</p>
                                        <p class="mt-8 text-sm font-semibold text-olive-800">{{ trans_choice('site.service_count', $lineServices->count(), ['count' => $lineServices->count()]) }}</p>

                                        <svg aria-hidden="true" class="absolute bottom-7 right-7 h-20 w-32 text-olive-700/35" viewBox="0 0 160 90" fill="none">
                                            <path d="M3 72C28 8 59 9 77 45C94 79 128 73 157 14" stroke="currentColor" stroke-width="2"/>
                                            <path d="M3 84C34 21 70 23 84 55C98 83 128 80 157 35" stroke="currentColor" stroke-width="1.5" opacity=".7"/>
                                            <circle cx="77" cy="45" r="5" fill="var(--brand-paper)" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </aside>

                                    <div class="territory-service-grid">
                                        @foreach ($lineServices as $service)
                                            <article class="territory-service">
                                                <div class="service-type">
                                                    {{ __('services.types.'.$service->service_type) !== 'services.types.'.$service->service_type ? __('services.types.'.$service->service_type) : $service->localizedName() }}
                                                </div>
                                                <h4 class="mt-5 text-2xl font-semibold leading-8 tracking-[-0.025em] text-igna-charcoal">{{ $service->localizedName() }}</h4>
                                                <p class="mt-3 max-w-[40ch] text-[16px] leading-7 text-stone-600">{{ $service->localizedDescription() }}</p>
                                                @if ($service->service_scope && $service->service_scope !== 'none')
                                                    <p class="service-scope mt-5">
                                                        {{ __('services.scopes.'.$service->service_scope) !== 'services.scopes.'.$service->service_scope ? __('services.scopes.'.$service->service_scope) : $service->service_scope }}
                                                    </p>
                                                @endif
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            </section>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section id="process" class="flow-section">
            <div class="site-shell relative z-10 py-24 lg:py-32">
                <div class="max-w-4xl">
                    <p class="section-kicker text-olive-200">{{ __('site.nav_process') }}</p>
                    <h2 class="mt-4 max-w-4xl text-4xl font-semibold leading-[1.03] tracking-[-0.035em] text-white sm:text-5xl lg:text-6xl">{{ __('site.process_title') }}</h2>
                </div>

                <div class="relative mt-14 lg:mt-18">
                    <svg aria-hidden="true" class="absolute left-0 top-5 hidden h-24 w-full text-olive-200 lg:block" viewBox="0 0 1200 100" fill="none" preserveAspectRatio="none">
                        <path class="route-line" d="M35 52H265C310 52 322 16 367 16H574C619 16 631 78 676 78H901C946 78 958 37 1003 37H1172" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <div class="grid gap-6 lg:grid-cols-4 lg:gap-0">
                        @foreach ($process as $index => $step)
                            <article class="process-step lg:px-5 lg:pt-16 lg:first:pl-0">
                                <p class="text-sm font-semibold tracking-[0.12em] text-olive-200">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</p>
                                <p class="mt-4 max-w-[27ch] text-[17px] leading-8 text-stone-100">{{ $step }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section id="projects" class="topographic-field py-24 lg:py-32">
            <div class="site-shell grid gap-12 lg:grid-cols-[0.78fr_1.22fr] lg:gap-20">
                <div class="lg:pt-3">
                    <p class="section-kicker">{{ __('site.nav_projects') }}</p>
                    <h2 class="section-title">{{ __('site.projects_title') }}</h2>
                    <svg aria-hidden="true" class="mt-12 hidden h-40 w-full max-w-sm text-olive-700/35 lg:block" viewBox="0 0 360 170" fill="none">
                        <path d="M2 149C63 57 129 165 192 81C229 32 290 80 358 4" stroke="currentColor" stroke-width="2"/>
                        <path d="M2 161C72 69 138 173 206 91C246 43 300 90 358 26" stroke="currentColor" stroke-width="1.4" opacity=".8"/>
                        <circle cx="192" cy="81" r="7" fill="var(--brand-paper)" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="case-ledger">
                    @foreach ($projects as $project)
                        <article class="case-entry">
                            <p class="text-sm font-semibold tracking-[0.12em] text-olive-700">{{ $project['type'] }}</p>
                            <h3 class="mt-3 max-w-2xl text-3xl font-semibold leading-tight tracking-[-0.03em] text-igna-charcoal">{{ $project['name'] }}</h3>
                            <p class="mt-4 max-w-[62ch] text-[17px] leading-8 text-stone-600">{{ $project['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="team" class="expert-stage py-24 lg:py-32">
            <div class="site-shell">
                <div class="max-w-4xl">
                    <p class="section-kicker">{{ __('site.nav_team') }}</p>
                    <h2 class="section-title">{{ __('site.team_title') }}</h2>
                    <p class="section-copy">{{ __('site.team_intro') }}</p>
                </div>

                <div class="mt-14 grid gap-0 lg:grid-cols-2 lg:mt-18">
                    @foreach ($teamProfiles as $profile)
                        <article class="expert-profile group">
                            <div class="grid gap-6 sm:grid-cols-[9.5rem_1fr] sm:items-start">
                                <x-team.photo :member="$profile" variant="card" />
                                <div class="min-w-0">
                                    <p class="section-kicker">{{ $profile->role }}</p>
                                    <h3 class="mt-3 text-3xl font-semibold leading-tight tracking-[-0.03em] text-igna-charcoal">{{ $profile->name }}</h3>
                                    <p class="mt-4 max-w-md text-[17px] leading-8 text-stone-600">{{ $profile->short_description }}</p>
                                    @if ($profile->publicCredentials->isNotEmpty())
                                        <p class="mt-4 text-sm font-semibold text-olive-800">{{ trans_choice('site.credential_count', $profile->publicCredentials->count(), ['count' => $profile->publicCredentials->count()]) }}</p>
                                    @endif
                                    <a href="{{ route('team.show', $profile->slug) }}" class="mt-6 inline-flex border-b border-olive-700 pb-1 text-sm font-semibold text-olive-800 transition hover:text-olive-700">{{ __('site.view_profile') }}</a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="py-24 lg:py-32">
            <div class="site-shell">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-4xl">
                        <p class="section-kicker">{{ __('site.nav_blog') }}</p>
                        <h2 class="section-title">{{ __('site.blog_title') }}</h2>
                    </div>
                    <a href="{{ route('blog.index') }}" class="inline-flex w-fit border-b border-olive-700 pb-1 text-sm font-semibold text-olive-800 transition hover:text-olive-700">{{ __('site.view_all_posts') }}</a>
                </div>

                <div class="mt-12 grid gap-x-10 lg:grid-cols-[1.15fr_0.85fr_0.85fr] lg:mt-16">
                    @forelse ($posts as $post)
                        <article class="journal-entry">
                            <p class="text-sm font-semibold tracking-[0.12em] text-olive-700">{{ optional($post->published_at)->format('Y-m-d') }}</p>
                            <h3 class="mt-4 text-3xl font-semibold leading-tight tracking-[-0.03em] text-igna-charcoal">{{ $post->localizedTitle() }}</h3>
                            <p class="mt-4 flex-1 text-[17px] leading-8 text-stone-600">{{ $post->localizedSummary() }}</p>
                            <a href="{{ route('blog.show', $post) }}" class="mt-6 inline-flex w-fit border-b border-olive-700 pb-1 text-sm font-semibold text-olive-800 transition hover:text-olive-700">{{ __('site.read_article') }}</a>
                        </article>
                    @empty
                        <div class="border-t border-dashed border-stone-300 py-8 text-sm text-stone-500 lg:col-span-3">
                            {{ __('site.blog_empty') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="request" class="pb-24 lg:pb-32">
            <div class="site-shell">
                <div class="request-route grid overflow-hidden lg:grid-cols-[0.72fr_1.28fr]">
                    <div class="relative z-10 p-7 sm:p-10 lg:p-14">
                        <p class="section-kicker">{{ __('site.request_title') }}</p>
                        <h2 class="mt-4 max-w-xl text-4xl font-semibold leading-[1.05] tracking-[-0.035em] text-igna-charcoal lg:text-5xl">{{ __('site.request_heading') }}</h2>
                        <p class="mt-6 max-w-lg text-[17px] leading-8 text-stone-600">{{ __('site.request_intro') }}</p>
                        <div class="mt-10 border-l border-olive-700 pl-5">
                            <p class="text-sm font-semibold text-olive-800">{{ __('site.cta_track') }}</p>
                            <a href="{{ route('tracking.index') }}" class="mt-3 inline-flex border-b border-olive-700 pb-1 text-sm font-semibold text-olive-800 transition hover:text-olive-700">{{ __('site.nav_tracking') }}</a>
                        </div>
                    </div>

                    <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" class="request-form-surface relative z-10 grid gap-5 p-7 sm:grid-cols-2 sm:p-10 lg:p-14">
                        @csrf
                        <div>
                            <label class="form-label">{{ __('site.form_first_name') }}</label>
                            <input name="first_name" value="{{ old('first_name') }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_last_name') }}</label>
                            <input name="last_name" value="{{ old('last_name') }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_email') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_phone') }}</label>
                            <input name="phone" value="{{ old('phone') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_project_name') }}</label>
                            <input name="project_name" value="{{ old('project_name') }}" class="form-input" required>
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_project_location') }}</label>
                            <input name="project_location" value="{{ old('project_location') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_preferred_language') }}</label>
                            <select name="preferred_language" class="form-input" required>
                                <option value="es" @selected(old('preferred_language', app()->getLocale()) === 'es')>{{ __('site.language_spanish') }}</option>
                                <option value="en" @selected(old('preferred_language', app()->getLocale()) === 'en')>{{ __('site.language_english') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_service') }}</label>
                            <select name="service_id" class="form-input" required>
                                <option value="">{{ __('site.form_choose_service') }}</option>
                                @foreach ($requestServiceGroups as $categoryCode => $groupedServices)
                                    @if ($groupedServices->isNotEmpty())
                                        <optgroup label="{{ __('site.service_public_category_'.$categoryCode) }}">
                                            @foreach ($groupedServices as $service)
                                                <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>
                                                    {{ $service->localizedName() }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                                <option value="other" @selected(old('service_id') === 'other')>{{ __('site.service_public_category_other') }}</option>
                            </select>
                            <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.form_service_help') }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">{{ __('site.form_description') }}</label>
                            <textarea name="project_description" rows="6" class="form-input" required>{{ old('project_description') }}</textarea>
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_target_date') }}</label>
                            <input type="date" name="target_date" value="{{ old('target_date') }}" class="form-input">
                        </div>
                        <div>
                            <label class="form-label">{{ __('site.form_initial_file') }}</label>
                            <input type="file" name="initial_file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="form-input">
                            <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.form_initial_file_help') }}</p>
                        </div>
                        <div class="sm:col-span-2 pt-2">
                            <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white shadow-[0_16px_36px_rgba(52,83,66,0.22)] transition hover:-translate-y-0.5 hover:bg-olive-800 active:translate-y-0">
                                {{ __('site.cta_submit_request') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>
@endsection
