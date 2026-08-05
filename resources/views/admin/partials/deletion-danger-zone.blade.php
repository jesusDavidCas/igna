@props([
    'action',
    'entityKey',
    'entityType',
    'identifier',
    'label' => null,
    'impact',
    'blockedMessage' => null,
])

@php
    $modalId = 'delete-'.$entityKey.'-'.md5($identifier);
@endphp

<x-admin.compact-delete-control
    :action="$action"
    :title="__('site.deletion_compact_title_'.$entityKey)"
    :warning="__('site.deletion_compact_warning_'.$entityKey)"
    :modal-id="$modalId"
    :modal-title="__('site.deletion_modal_title_'.$entityKey)"
    :modal-question="__('site.deletion_modal_question_'.$entityKey)"
    :identifier="$identifier"
    :modal-consequence="__('site.deletion_modal_consequence_'.$entityKey)"
    :blocked-message="$blockedMessage"
    :can-delete="$impact->canDelete()"
/>
