@extends('layouts.panel', ['title' => __('site.new_proposal'), 'heading' => __('site.new_proposal')])

@section('content')
    @include('admin.proposals.partials.form', [
        'action' => route('admin.proposals.store'),
        'method' => 'POST',
        'proposal' => $proposal,
        'clients' => $clients,
        'signers' => $signers,
        'selectedClientId' => $selectedClientId,
        'selectedSignerId' => $selectedSignerId,
        'paymentSchedule' => $paymentSchedule,
        'items' => $items,
    ])
@endsection
