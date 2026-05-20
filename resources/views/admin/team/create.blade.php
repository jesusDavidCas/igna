@extends('layouts.panel', ['title' => __('site.new_team_member'), 'heading' => __('site.new_team_member')])

@section('content')
    @include('admin.team.partials.form', [
        'action' => route('admin.team.store'),
        'method' => 'POST',
        'teamMember' => $teamMember,
    ])
@endsection
