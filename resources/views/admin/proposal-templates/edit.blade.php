@extends('layouts.panel', ['title' => __('site.edit_proposal_template'), 'heading' => __('site.edit_proposal_template')])

@section('content')
    @include('admin.proposal-templates.partials.form', [
        'action' => route('admin.proposal-templates.update', $proposalTemplate),
        'method' => 'PUT',
    ])
@endsection
