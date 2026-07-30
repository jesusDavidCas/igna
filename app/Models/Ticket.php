<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_code',
        'service_id',
        'service_selection',
        'service_public_category',
        'client_user_id',
        'current_service_stage_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'project_name',
        'project_location',
        'preferred_language',
        'project_description',
        'target_date',
        'status',
        'google_drive_folder_id',
        'google_drive_folder_url',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'submitted_at' => 'datetime',
            'status' => TicketStatus::class,
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function hasCatalogService(): bool
    {
        return $this->service_id !== null;
    }

    public function serviceDisplayName(): string
    {
        if ($this->relationLoaded('service') ? $this->service !== null : $this->service()->exists()) {
            return $this->service?->localizedName() ?? __('site.service_public_category_other');
        }

        return __('site.service_public_category_other');
    }

    public function serviceCategoryLabel(): string
    {
        $category = $this->service_public_category ?: $this->service?->publicCategoryCode();

        return $category ? __("site.service_public_category_{$category}") : __('site.service_public_category_other');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(ServiceStage::class, 'current_service_stage_id');
    }

    public function stageEvents(): HasMany
    {
        return $this->hasMany(TicketStageEvent::class)->orderBy('service_stage_id');
    }

    public function stageAudits(): HasMany
    {
        return $this->hasMany(TicketStageAudit::class)->latest();
    }

    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class)->latest('uploaded_at');
    }

    public function deliverables(): HasMany
    {
        return $this->hasMany(TicketDeliverable::class)->orderBy('sort_order');
    }

    public function localizedProjectName(): string
    {
        return $this->localizedDemoValue('project_name', $this->project_name);
    }

    public function localizedProjectDescription(): string
    {
        return $this->localizedDemoValue('project_description', $this->project_description);
    }

    private function localizedDemoValue(string $field, string $fallback): string
    {
        $key = 'demo.tickets.'.Str::slug($this->project_name, '_').".{$field}";
        $value = __($key);

        return $value === $key ? $fallback : $value;
    }
}
