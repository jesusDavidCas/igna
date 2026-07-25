<?php

namespace App\Models;

use App\Enums\StageEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStageEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'service_stage_id',
        'changed_by_user_id',
        'status',
        'is_client_visible',
        'notes',
        'attempt_number',
        'entered_at',
        'completed_at',
        'superseded_at',
        'superseded_by_user_id',
        'superseded_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => StageEventStatus::class,
            'is_client_visible' => 'boolean',
            'attempt_number' => 'integer',
            'entered_at' => 'datetime',
            'completed_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function serviceStage(): BelongsTo
    {
        return $this->belongsTo(ServiceStage::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superseded_by_user_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(TicketStageAudit::class);
    }
}
