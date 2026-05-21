@extends('layouts.public', ['title' => 'IGNA Studio'])

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
                'tone' => 'border-olive-200 bg-gradient-to-br from-olive-50 via-white to-stone-50',
                'badge' => 'bg-white text-olive-900 border-olive-200',
                'card' => 'border-olive-100 bg-white/85',
            ],
            'digital' => [
                'eyebrow' => __('site.services_digital_eyebrow'),
                'title' => __('site.services_digital_title'),
                'intro' => __('site.services_digital_intro'),
                'tone' => 'border-stone-200 bg-white',
                'badge' => 'bg-stone-100 text-stone-700 border-stone-200',
                'card' => 'border-stone-200 bg-stone-50/70',
            ],
        ]);
    @endphp

    <section class="relative overflow-hidden bg-gradient-to-b from-stone-100 via-stone-50 to-stone-50">
        <div class="absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top_left,_rgba(104,123,92,0.22),_transparent_50%)]"></div>
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-28">
            <div class="relative">
                <p class="inline-flex rounded-full border border-olive-300 bg-olive-100 px-4 py-1.5 text-xs font-semibold uppercase leading-5 tracking-[0.18em] text-olive-900 sm:text-sm sm:tracking-[0.2em]">
                    {{ __('site.hero_eyebrow') }}
                </p>
                <h1 class="mt-6 max-w-4xl text-[2.35rem] font-semibold leading-[1.1] text-stone-950 sm:text-5xl lg:text-6xl">
                    {{ __('site.hero_title') }}
                </h1>
                <p class="mt-5 max-w-2xl text-[17px] leading-8 text-stone-600 sm:mt-6 sm:text-lg">
                    {{ __('site.hero_description') }}
                </p>
                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="#request" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">
                        {{ __('site.cta_request') }}
                    </a>
                    <a href="{{ route('tracking.index') }}" class="rounded-full border border-stone-300 px-6 py-3 text-sm font-semibold text-stone-700 transition hover:border-olive-600 hover:text-olive-700">
                        {{ __('site.cta_track') }}
                    </a>
                </div>
            </div>
            <div class="grid gap-4">
                <div class="rounded-[2rem] border border-stone-200 bg-white p-7 shadow-lg shadow-stone-200/50">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ __('site.hero_card_1_label') }}</p>
                    <p class="mt-4 text-2xl font-semibold text-stone-950">{{ __('site.hero_card_1_title') }}</p>
                    <p class="mt-3 text-[17px] leading-8 text-stone-600">{{ __('site.hero_card_1_body') }}</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-[2rem] bg-stone-950 p-7 text-stone-100">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-olive-300">{{ __('site.hero_card_2_label') }}</p>
                        <p class="mt-4 text-lg font-semibold">{{ __('site.hero_card_2_title') }}</p>
                        <p class="mt-3 text-[17px] leading-8 text-stone-300">{{ __('site.hero_card_2_body') }}</p>
                    </div>
                    <div class="rounded-[2rem] border border-olive-200 bg-olive-50 p-7">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-olive-900">{{ __('site.hero_card_3_label') }}</p>
                        <p class="mt-4 text-lg font-semibold text-stone-950">{{ __('site.hero_card_3_title') }}</p>
                        <p class="mt-3 text-[17px] leading-8 text-stone-700">{{ __('site.hero_card_3_body') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="services" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-3xl">
            <p class="section-eyebrow">{{ __('site.nav_services') }}</p>
            <h2 class="section-title">{{ __('site.services_title') }}</h2>
            <p class="section-copy">{{ __('site.services_intro') }}</p>
        </div>
        <div class="mt-10 space-y-8">
            @foreach ($serviceGroups as $line => $group)
                @php
                    $lineServices = $services->where('business_line', $line);
                @endphp

                @if ($lineServices->isNotEmpty())
                    <div class="rounded-[2.5rem] border {{ $group['tone'] }} p-5 shadow-sm sm:p-7 lg:p-8">
                        <div class="grid gap-8 lg:grid-cols-[0.78fr_1.22fr] lg:items-start">
                            <div class="lg:sticky lg:top-24">
                                <span class="inline-flex rounded-full border px-4 py-1.5 text-sm font-semibold uppercase tracking-[0.16em] {{ $group['badge'] }}">
                                    {{ $group['eyebrow'] }}
                                </span>
                                <h3 class="mt-5 text-3xl font-semibold leading-tight text-stone-950">{{ $group['title'] }}</h3>
                                <p class="mt-4 text-[17px] leading-8 text-stone-600">{{ $group['intro'] }}</p>
                                <p class="mt-5 text-sm font-semibold uppercase tracking-[0.16em] text-stone-400">
                                    {{ trans_choice('site.service_count', $lineServices->count(), ['count' => $lineServices->count()]) }}
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                @foreach ($lineServices as $service)
                                    <article class="group rounded-[1.75rem] border {{ $group['card'] }} p-5 transition duration-200 hover:-translate-y-0.5 hover:border-olive-300 hover:shadow-md hover:shadow-stone-200/60">
                                        <div class="flex min-h-11 items-center justify-center rounded-2xl bg-white px-4 py-2 text-center text-[15px] font-semibold leading-5 text-olive-900 shadow-sm ring-1 ring-stone-200/70">
                                            {{ __('services.types.'.$service->service_type) !== 'services.types.'.$service->service_type ? __('services.types.'.$service->service_type) : $service->localizedName() }}
                                        </div>

                                        <h4 class="mt-4 text-xl font-semibold leading-7 text-stone-950">{{ $service->localizedName() }}</h4>
                                        <p class="mt-3 text-[16px] leading-7 text-stone-600">{{ $service->localizedDescription() }}</p>

                                        @if ($service->service_scope && $service->service_scope !== 'none')
                                            <p class="mt-4 inline-flex rounded-full bg-stone-100 px-3 py-1 text-sm font-medium text-stone-600">
                                                {{ __('services.scopes.'.$service->service_scope) !== 'services.scopes.'.$service->service_scope ? __('services.scopes.'.$service->service_scope) : $service->service_scope }}
                                            </p>
                                        @endif

                                        @if ($service->stages->isNotEmpty())
                                            <div class="mt-5 flex flex-wrap gap-2">
                                                @foreach ($service->stages->take(3) as $stage)
                                                    <span class="inline-flex min-h-8 items-center justify-center rounded-full bg-white px-3 py-1 text-center text-sm font-medium leading-5 text-stone-500 ring-1 ring-stone-200">{{ $stage->localizedName() }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>

    <section id="process" class="bg-stone-100/70">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
            <div class="max-w-3xl">
                <p class="section-eyebrow">{{ __('site.nav_process') }}</p>
                <h2 class="section-title">{{ __('site.process_title') }}</h2>
            </div>
            <div class="relative mt-12 grid gap-6 lg:grid-cols-4">
                <div class="pointer-events-none absolute left-[9%] right-[9%] top-10 hidden h-3 rounded-full bg-olive-200 shadow-[0_0_28px_rgba(104,123,92,0.28)] lg:block"></div>
                <div class="pointer-events-none absolute left-[9%] right-[9%] top-10 hidden h-3 rounded-full bg-gradient-to-r from-olive-700 via-olive-500 to-olive-300 lg:block"></div>
                @foreach ($process as $index => $step)
                    <div class="relative z-10 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
                        @if (! $loop->last)
                            <div class="absolute -right-5 top-8 z-20 hidden h-8 w-8 items-center justify-center rounded-full bg-olive-700 text-white shadow-lg shadow-olive-900/20 lg:flex">
                                <span class="text-lg leading-none">&rarr;</span>
                            </div>
                        @endif
                        @if (! $loop->first)
                            <div class="absolute -top-6 left-8 h-6 w-1 rounded-full bg-olive-300 lg:hidden"></div>
                        @endif
                        <div class="flex h-20 w-20 items-center justify-center rounded-full border-4 border-white bg-olive-700 text-base font-semibold text-white shadow-xl shadow-olive-900/20 ring-4 ring-olive-200">
                            0{{ $index + 1 }}
                        </div>
                        <p class="mt-4 text-[17px] leading-8 text-stone-700">{{ $step }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="projects" class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="max-w-3xl">
            <p class="section-eyebrow">{{ __('site.nav_projects') }}</p>
            <h2 class="section-title">{{ __('site.projects_title') }}</h2>
        </div>
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ($projects as $project)
                <article class="rounded-[2rem] border border-stone-200 bg-white p-7 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-stone-500">{{ $project['type'] }}</p>
                    <h3 class="mt-3 text-xl font-semibold text-stone-950">{{ $project['name'] }}</h3>
                    <p class="mt-4 text-[17px] leading-8 text-stone-600">{{ $project['description'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section id="team" class="bg-stone-950 text-stone-100">
        <div class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
            <div class="max-w-3xl">
                <p class="section-eyebrow text-olive-300">{{ __('site.nav_team') }}</p>
                <h2 class="section-title text-white">{{ __('site.team_title') }}</h2>
                <p class="section-copy text-stone-300">{{ __('site.team_intro') }}</p>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-2">
                @foreach ($teamProfiles as $profile)
                    <article class="rounded-[2rem] border border-white/10 bg-white/5 p-7">
                        <div class="flex items-start gap-5">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-olive-700 text-lg font-semibold text-white">
                                @if ($profile->photoUrl())
                                    <img src="{{ $profile->photoUrl() }}" alt="{{ $profile->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ collect(explode(' ', $profile->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
                                @endif
                            </div>
                            <div>
                                <h3 class="text-2xl font-semibold">{{ $profile->name }}</h3>
                                <p class="mt-2 text-[15px] text-olive-300">{{ $profile->role }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-[17px] leading-8 text-stone-300">{{ $profile->short_description }}</p>
                        @if ($profile->publicCredentials->isNotEmpty())
                            <p class="mt-4 text-sm uppercase tracking-[0.18em] text-stone-400">{{ trans_choice('site.credential_count', $profile->publicCredentials->count(), ['count' => $profile->publicCredentials->count()]) }}</p>
                        @endif
                        <a href="{{ route('team.show', $profile->slug) }}" class="mt-6 inline-flex rounded-full border border-white/15 px-4 py-2 text-sm font-semibold text-white transition hover:border-olive-300 hover:text-olive-200">
                            {{ __('site.view_profile') }}
                        </a>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="section-eyebrow">{{ __('site.nav_blog') }}</p>
                <h2 class="section-title">{{ __('site.blog_title') }}</h2>
            </div>
            <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-olive-700 hover:text-olive-800">{{ __('site.view_all_posts') }}</a>
        </div>
        <div class="mt-10 grid gap-6 lg:grid-cols-3">
            @forelse ($posts as $post)
                <article class="rounded-[2rem] border border-stone-200 bg-white p-7 shadow-sm">
                    <p class="text-sm uppercase tracking-[0.18em] text-stone-500">{{ optional($post->published_at)->format('Y-m-d') }}</p>
                    <h3 class="mt-3 text-xl font-semibold text-stone-950">{{ $post->localizedTitle() }}</h3>
                    <p class="mt-4 text-[17px] leading-8 text-stone-600">{{ $post->localizedSummary() }}</p>
                    <a href="{{ route('blog.show', $post) }}" class="mt-5 inline-flex text-sm font-semibold text-olive-700">{{ __('site.read_article') }}</a>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-stone-300 bg-stone-50 p-8 text-sm text-stone-500 lg:col-span-3">
                    {{ __('site.blog_empty') }}
                </div>
            @endforelse
        </div>
    </section>

    <section id="request" class="bg-stone-100/70">
        <div class="mx-auto grid max-w-7xl gap-10 px-6 py-20 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
            <div>
                <p class="section-eyebrow">{{ __('site.request_title') }}</p>
                <h2 class="section-title">{{ __('site.request_heading') }}</h2>
                <p class="section-copy">{{ __('site.request_intro') }}</p>
            </div>
            <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" class="grid gap-5 rounded-[2rem] border border-stone-200 bg-white p-8 shadow-sm md:grid-cols-2">
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
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" @selected((string) old('service_id') === (string) $service->id)>
                                {{ $service->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">{{ __('site.form_description') }}</label>
                    <textarea name="project_description" rows="6" class="form-input" required>{{ old('project_description') }}</textarea>
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_target_date') }}</label>
                    <input type="date" name="target_date" value="{{ old('target_date') }}" class="form-input">
                </div>
                <div>
                    <label class="form-label">{{ __('site.form_initial_file') }}</label>
                    <input type="file" name="initial_file" class="form-input">
                    <p class="mt-2 text-[15px] leading-6 text-stone-500">{{ __('site.form_initial_file_help') }}</p>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="rounded-full bg-olive-700 px-6 py-3 text-sm font-semibold text-white transition hover:bg-olive-800">
                        {{ __('site.cta_submit_request') }}
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
