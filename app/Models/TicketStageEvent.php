<?php

namespace App\Models;

use App\Enums\StageEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'entered_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => StageEventStatus::class,
            'is_client_visible' => 'boolean',
            'entered_at' => 'datetime',
            'completed_at' => 'datetime',
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
}
