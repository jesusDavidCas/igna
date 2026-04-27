@extends('layouts.panel', ['title' => $user->name, 'heading' => $user->name])

@section('content')
    @include('admin.users.partials.form', [
        'action' => route('admin.users.update', $user),
        'method' => 'PUT',
        'user' => $user,
        'roles' => $roles,
    ])
@endsection
