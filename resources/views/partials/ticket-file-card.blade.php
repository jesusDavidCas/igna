<article class="ticket-file-card" data-file-card data-file-row>
    <div class="ticket-file-card__body">
        <div class="ticket-file-card__info" data-file-card-info>
            <h4 class="ticket-file-card__title">{{ $file->title }}</h4>
            <p class="ticket-file-card__filename" title="{{ $file->original_name }}" aria-label="{{ $file->original_name }}">{{ $file->original_name }}</p>
            <div class="ticket-file-card__metadata">
                <span>{{ $file->categoryLabel() }}</span>
                @if ($file->uploaded_at)
                    <span>{{ $file->uploaded_at->format('Y-m-d H:i') }}</span>
                @endif
                @if ($file->uploadedBy)
                    <span>{{ $file->uploadedBy->name }}</span>
                @elseif ($file->isClientSubmitted())
                    <span>{{ $file->uploadSourceLabel() }}</span>
                @endif
            </div>
            @if ($file->isClientSubmitted() && $file->reviewStatusDateLabel())
                <p class="ticket-file-card__status-note">{{ $file->reviewStatusDateLabel() }}</p>
            @endif
            @if ($file->review_status === 'rejected' && $file->rejection_reason)
                <p class="ticket-file-card__rejection-note">{{ __('site.rejection_reason') }}: {{ $file->rejection_reason }}</p>
            @endif
        </div>

        <div class="ticket-file-card__badges" data-file-card-badges>
            <span class="ticket-file-card__badge ticket-file-card__badge--neutral">{{ $file->categoryLabel() }}</span>
            <span class="ticket-file-card__badge ticket-file-card__badge--neutral">{{ $file->deliveryTypeLabel() }}</span>
            @if ($file->isClientSubmitted())
                <span class="ticket-file-card__badge ticket-file-card__badge--{{ $file->review_status }}">{{ $file->reviewStatusLabel() }}</span>
            @endif
            <span class="ticket-file-card__badge {{ $file->is_client_visible ? 'ticket-file-card__badge--visible' : 'ticket-file-card__badge--neutral' }}">
                {{ $file->is_client_visible ? __('site.client_visible') : __('site.internal_only') }}
            </span>
        </div>

        <div class="ticket-file-card__actions" data-file-card-actions data-file-actions>
            <a href="{{ $downloadUrl }}" class="ticket-file-card__action ticket-file-card__action--primary">
                {{ __('site.download_file') }}
            </a>
            @isset($visibilityRoute)
                <form method="POST" action="{{ $visibilityRoute }}" data-confirm-title="{{ __('site.confirm_file_visibility_title') }}" data-confirm-message="{{ __('site.confirm_file_visibility_message') }}">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="ticket-file-card__action ticket-file-card__action--secondary">
                        {{ $file->is_client_visible ? __('site.hide_from_client') : __('site.make_available_to_client') }}
                    </button>
                </form>
            @endisset
            @isset($reviewRoute)
                @if ($file->review_status !== 'reviewed')
                    <form method="POST" action="{{ $reviewRoute }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="ticket-file-card__action ticket-file-card__action--positive">
                            {{ __('site.mark_reviewed') }}
                        </button>
                    </form>
                @endif
            @endisset
            @isset($rejectRoute)
                @if ($file->review_status !== 'rejected')
                    <form method="POST" action="{{ $rejectRoute }}" class="ticket-file-card__reject-form">
                        @csrf
                        @method('PATCH')
                        <label for="file-rejection-reason-{{ $file->id }}" class="sr-only">{{ __('site.rejection_reason') }}</label>
                        <input id="file-rejection-reason-{{ $file->id }}" name="rejection_reason" class="ticket-file-card__reject-input" placeholder="{{ __('site.rejection_reason') }}">
                        <button type="submit" class="ticket-file-card__action ticket-file-card__action--danger">
                            {{ __('site.reject_document') }}
                        </button>
                    </form>
                @endif
            @endisset
            @isset($deleteRoute)
                <form method="POST" action="{{ $deleteRoute }}" data-confirm-title="{{ __('site.confirm_delete_file_title') }}" data-confirm-message="{{ __('site.confirm_delete_file_message') }}" data-confirm-danger="true">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ticket-file-card__action ticket-file-card__action--danger">
                        {{ __('site.delete') }}
                    </button>
                </form>
            @endisset
        </div>
    </div>
</article>
