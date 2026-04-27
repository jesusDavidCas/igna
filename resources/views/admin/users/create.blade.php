@extends('layouts.panel', ['title' => __('site.new_user'), 'heading' => __('site.new_user')])

@section('content')
    @include('admin.users.partials.form', [
        'action' => route('admin.users.store'),
        'method' => 'POST',
        'user' => $user,
        'roles' => $roles,
    ])
@endsection
