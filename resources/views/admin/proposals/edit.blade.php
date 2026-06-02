@extends('layouts.panel', ['title' => $proposal->proposal_number, 'heading' => $proposal->proposal_number])

@section('content')
    @include('admin.proposals.partials.form', [
        'action' => route('admin.proposals.update', $proposal),
        'method' => 'PUT',
        'proposal' => $proposal,
        'clients' => $clients,
        'signers' => $signers,
        'selectedClientId' => $selectedClientId,
        'selectedSignerId' => $selectedSignerId,
        'paymentSchedule' => $paymentSchedule,
        'items' => $items,
        'proposalTemplates' => $proposalTemplates,
        'proposalTemplatePayload' => $proposalTemplatePayload,
    ])
@endsection
