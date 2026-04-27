<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'uploaded_by_user_id',
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
        'is_client_visible',
        'watermark_status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'is_client_visible' => 'boolean',
            'uploaded_at' => 'datetime',
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

    public function storageProviderLabel(): string
    {
        $translationKey = "site.storage_provider_{$this->storage_provider}";

        return __($translationKey) === $translationKey
            ? $this->storage_provider
            : __($translationKey);
    }
}
