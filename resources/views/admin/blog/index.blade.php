@extends('layouts.panel', ['title' => __('site.admin_blog'), 'heading' => __('site.admin_blog')])

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-stone-500">{{ __('site.blog_admin_intro') }}</p>
        <a href="{{ route('admin.blog.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_post') }}</a>
    </div>

    <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="pb-3">{{ __('site.form_title') }}</th>
                        <th class="pb-3">{{ __('site.form_status') }}</th>
                        <th class="pb-3">{{ __('site.form_slug') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($posts as $post)
                        <tr>
                            <td class="py-3"><a class="font-semibold text-olive-700" href="{{ route('admin.blog.edit', $post) }}">{{ $post->title }}</a></td>
                            <td class="py-3">{{ $post->status->label() }}</td>
                            <td class="py-3">{{ $post->slug }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        {{ $posts->links() }}
    </div>
@endsection
