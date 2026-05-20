<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketDeliverable extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'service_deliverable_id',
        'name',
        'description',
        'status',
        'sort_order',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function serviceDeliverable(): BelongsTo
    {
        return $this->belongsTo(ServiceDeliverable::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class)->latest('uploaded_at');
    }

    public function statusLabel(): string
    {
        $key = "site.deliverable_status_{$this->status}";

        return __($key) === $key ? str($this->status)->headline()->toString() : __($key);
    }
}
