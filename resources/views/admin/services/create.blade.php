@extends('layouts.panel', ['title' => __('site.new_service'), 'heading' => __('site.new_service')])

@section('content')
    @include('admin.services.partials.form', [
        'action' => route('admin.services.store'),
        'method' => 'POST',
        'service' => $service,
        'serviceTypes' => $serviceTypes,
        'serviceScopes' => $serviceScopes,
    ])
@endsection
