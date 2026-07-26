<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketStageAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'ticket_stage_event_id',
        'service_stage_id',
        'actor_user_id',
        'action',
        'status_before',
        'status_after',
        'attempt_number',
        'entered_at_snapshot',
        'completed_at_snapshot',
        'notes_snapshot',
        'rollback_from_stage_id',
        'rollback_to_stage_id',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'entered_at_snapshot' => 'datetime',
            'completed_at_snapshot' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(TicketStageEvent::class, 'ticket_stage_event_id');
    }

    public function serviceStage(): BelongsTo
    {
        return $this->belongsTo(ServiceStage::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
