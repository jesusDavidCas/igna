@extends('layouts.panel', ['title' => __('site.admin_users'), 'heading' => __('site.admin_users')])

@section('content')
    <div class="flex items-center justify-between">
        <p class="text-sm text-stone-500">{{ __('site.users_admin_intro') }}</p>
        <a href="{{ route('admin.users.create') }}" class="rounded-full bg-olive-700 px-5 py-2.5 text-sm font-semibold text-white">{{ __('site.new_user') }}</a>
    </div>

    <div class="mt-8 rounded-[2rem] border border-stone-200 bg-white p-6 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="text-stone-500">
                    <tr>
                        <th class="pb-3">{{ __('site.form_name') }}</th>
                        <th class="pb-3">{{ __('site.form_email') }}</th>
                        <th class="pb-3">{{ __('site.form_role') }}</th>
                        <th class="pb-3">{{ __('site.form_status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($users as $user)
                        <tr>
                            <td class="py-3"><a class="font-semibold text-olive-700" href="{{ route('admin.users.edit', $user) }}">{{ $user->name }}</a></td>
                            <td class="py-3">{{ $user->email }}</td>
                            <td class="py-3">{{ $user->role->label() }}</td>
                            <td class="py-3">{{ $user->is_active ? __('site.active') : __('site.inactive') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-8">
        {{ $users->links() }}
    </div>
@endsection
