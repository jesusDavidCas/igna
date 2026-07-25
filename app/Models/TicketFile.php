<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'uploaded_by_user_id',
        'ticket_deliverable_id',
        'title',
        'original_name',
        'stored_name',
        'mime_type',
        'size_bytes',
        'storage_provider',
        'storage_disk',
        'storage_path',
        'google_drive_file_id',
        'google_drive_url',
        'deliverable_type',
        'visibility',
        'delivery_type',
        'upload_source',
        'review_status',
        'submitted_context_hash',
        'first_admin_downloaded_by_user_id',
        'first_admin_downloaded_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejected_by_user_id',
        'rejected_at',
        'rejection_reason',
        'is_client_visible',
        'watermark_status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_client_visible' => 'boolean',
            'uploaded_at' => 'datetime',
            'first_admin_downloaded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function firstAdminDownloadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'first_admin_downloaded_by_user_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(TicketDeliverable::class, 'ticket_deliverable_id');
    }

    public function scopeClientVisible(Builder $query): Builder
    {
        return $query
            ->where('is_client_visible', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('ticket_deliverable_id')
                    ->orWhereHas('deliverable', function (Builder $deliverable): void {
                        $deliverable
                            ->whereColumn('ticket_deliverables.ticket_id', 'ticket_files.ticket_id')
                            ->where(function (Builder $deliverable): void {
                                $deliverable
                                    ->whereNull('service_deliverable_id')
                                    ->orWhereHas('serviceDeliverable', function (Builder $serviceDeliverable): void {
                                        $serviceDeliverable->where('is_client_visible_by_default', true);
                                    });
                            });
                    });
            });
    }

    public function scopeClientSubmitted(Builder $query): Builder
    {
        return $query->whereIn('upload_source', [
            'initial_request',
            'authenticated_client',
            'public_tracking',
        ]);
    }

    public function scopeGeneralProjectFile(Builder $query): Builder
    {
        return $query
            ->where('upload_source', 'admin')
            ->whereNull('ticket_deliverable_id');
    }

    public function isClientSubmitted(): bool
    {
        return in_array($this->upload_source, ['initial_request', 'authenticated_client', 'public_tracking'], true);
    }

    public function categoryLabel(): string
    {
        $key = "site.ticket_file_category_{$this->deliverable_type}";

        return __($key) === $key
            ? str((string) $this->deliverable_type)->replace('_', ' ')->headline()->toString()
            : __($key);
    }

    public function deliveryTypeLabel(): string
    {
        $key = "site.delivery_type_{$this->delivery_type}";

        return __($key) === $key ? str($this->delivery_type)->headline()->toString() : __($key);
    }

    public function storageProviderLabel(): string
    {
        $translationKey = "site.storage_provider_{$this->storage_provider}";

        return __($translationKey) === $translationKey
            ? $this->storage_provider
            : __($translationKey);
    }

    public function uploadSourceLabel(): string
    {
        $key = "site.ticket_file_source_{$this->upload_source}";

        return __($key) === $key
            ? str((string) $this->upload_source)->replace('_', ' ')->headline()->toString()
            : __($key);
    }

    public function reviewStatusLabel(): string
    {
        $key = "site.ticket_file_review_status_{$this->review_status}";

        return __($key) === $key
            ? str((string) $this->review_status)->replace('_', ' ')->headline()->toString()
            : __($key);
    }

    public function reviewStatusDateLabel(): ?string
    {
        return match ($this->review_status) {
            'downloaded' => $this->first_admin_downloaded_at
                ? __('site.ticket_file_downloaded_on', ['date' => $this->first_admin_downloaded_at->format('Y-m-d H:i')])
                : null,
            'reviewed' => $this->reviewed_at
                ? __('site.ticket_file_reviewed_on', ['date' => $this->reviewed_at->format('Y-m-d H:i')])
                : null,
            'rejected' => $this->rejected_at
                ? __('site.ticket_file_rejected_on', ['date' => $this->rejected_at->format('Y-m-d H:i')])
                : null,
            default => null,
        };
    }
}
