<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_number',
        'client_user_id',
        'created_by_user_id',
        'signer_user_id',
        'title',
        'subject',
        'description',
        'scope',
        'timeline_months',
        'timeline_weeks',
        'timeline',
        'payment_plan',
        'payment_schedule',
        'source_excel_path',
        'source_excel_original_name',
        'status',
        'tax_rate',
        'subtotal',
        'tax_total',
        'total',
        'issued_at',
        'valid_until',
        'validity_days',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'timeline_months' => 'integer',
            'timeline_weeks' => 'integer',
            'payment_schedule' => 'array',
            'issued_at' => 'date',
            'valid_until' => 'date',
            'validity_days' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class)->orderBy('sort_order');
    }

    public function statusLabel(): string
    {
        return __("site.proposal_status_{$this->status}");
    }

    public function formattedTimeline(): string
    {
        $parts = [];

        if ($this->timeline_months > 0) {
            $parts[] = trans_choice('site.timeline_month_count', $this->timeline_months, ['count' => $this->timeline_months]);
        }

        if ($this->timeline_weeks > 0) {
            $parts[] = trans_choice('site.timeline_week_count', $this->timeline_weeks, ['count' => $this->timeline_weeks]);
        }

        return $parts ? implode(' + ', $parts) : __('site.timeline_to_confirm');
    }

    public function validityLabel(): string
    {
        return __('site.proposal_validity_days', ['days' => $this->validity_days ?: 30]);
    }

    public function paymentScheduleRows(): array
    {
        return collect($this->payment_schedule ?? [])
            ->filter(fn (array $payment): bool => filled($payment['label'] ?? null) || filled($payment['percentage'] ?? null) || filled($payment['notes'] ?? null))
            ->values()
            ->all();
    }
}
