<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeletionAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'actor_email_snapshot',
        'entity_type',
        'entity_public_identifier',
        'entity_label',
        'dependency_summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'dependency_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
