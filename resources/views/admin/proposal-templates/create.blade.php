@extends('layouts.panel', ['title' => __('site.new_proposal_template'), 'heading' => __('site.new_proposal_template')])

@section('content')
    @include('admin.proposal-templates.partials.form', [
        'action' => route('admin.proposal-templates.store'),
        'method' => 'POST',
    ])
@endsection
