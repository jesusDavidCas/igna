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

    public function files(): HasMany
    {
        return $this->hasMany(TicketFile::class)->latest('uploaded_at');
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
