<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class Proposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposal_number',
        'public_token',
        'client_user_id',
        'prospect_name',
        'prospect_email',
        'prospect_phone',
        'project_location',
        'requested_deadline',
        'created_by_user_id',
        'signer_user_id',
        'title',
        'title_en',
        'title_es',
        'subject',
        'description',
        'scope',
        'timeline_months',
        'timeline_weeks',
        'timeline',
        'payment_plan',
        'payment_schedule',
        'status',
        'tax_rate',
        'subtotal',
        'tax_total',
        'total',
        'issued_at',
        'valid_until',
        'validity_days',
        'converted_to_project_at',
        'converted_by_user_id',
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
            'requested_deadline' => 'date',
            'converted_to_project_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Proposal $proposal): void {
            $proposal->public_token ??= static::newPublicToken();
        });
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

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'converted_by_user_id');
    }

    public function project(): HasOne
    {
        return $this->hasOne(Ticket::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class)->orderBy('sort_order');
    }

    public function statusLabel(): string
    {
        return __("site.proposal_status_{$this->status}");
    }

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'es') {
            return $this->isUsableTranslation($this->title_en, $this->title_es)
                ? $this->title_es
                : ($this->title_en ?: ($this->title ?: ''));
        }

        return $this->title_en ?: ($this->title ?: '');
    }

    public function scopePubliclyAccessible(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'approved']);
    }

    public function isPubliclyAccessible(): bool
    {
        return in_array($this->status, ['sent', 'approved'], true);
    }

    public function isProjectConvertible(): bool
    {
        if ($this->status !== 'approved') {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast() && ! $this->valid_until->isToday()) {
            return false;
        }

        return ! $this->project()->exists();
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
        if ($this->valid_until) {
            return __('site.valid_until').': '.$this->valid_until->format('Y-m-d');
        }

        return __('site.proposal_validity_days', ['days' => $this->validity_days ?: 30]);
    }

    public function publicUrl(): string
    {
        $publicToken = $this->ensurePublicToken();

        if ($publicToken) {
            return route('proposals.public.token.show', $publicToken);
        }

        return URL::signedRoute('proposals.public.show', $this);
    }

    public function publicRouteKey(): string
    {
        return $this->public_token ?: (string) $this->getKey();
    }

    public function ensurePublicToken(): ?string
    {
        if (filled($this->public_token)) {
            return $this->public_token;
        }

        if (! $this->exists || ! Schema::hasColumn($this->getTable(), 'public_token')) {
            return null;
        }

        $this->forceFill([
            'public_token' => static::newPublicToken(),
        ])->saveQuietly();

        return $this->public_token;
    }

    public function clientDisplayName(): string
    {
        return $this->client?->name
            ?: ($this->prospect_name ?: __('site.unassigned'));
    }

    public function clientDisplayEmail(): ?string
    {
        return $this->client?->email ?: $this->prospect_email;
    }

    public function clientDisplayPhone(): ?string
    {
        return $this->client?->phone ?: $this->prospect_phone;
    }

    public function paymentScheduleRows(): array
    {
        return collect($this->payment_schedule ?? [])
            ->filter(fn (array $payment): bool => filled($payment['label'] ?? null) || filled($payment['percentage'] ?? null) || filled($payment['notes'] ?? null))
            ->values()
            ->all();
    }

    private static function newPublicToken(): string
    {
        do {
            $token = Str::random(40);
        } while (static::query()->where('public_token', $token)->exists());

        return $token;
    }

    private function isUsableTranslation(?string $source, ?string $target): bool
    {
        $source = mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $source, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));
        $target = mb_strtolower(trim(preg_replace('/\s+/u', ' ', html_entity_decode((string) $target, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? ''));

        return $target !== '' && $target !== $source;
    }
}
