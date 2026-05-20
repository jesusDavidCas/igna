<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamCredentialView extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_credential_id',
        'user_id',
        'ip_hash',
        'user_agent',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(TeamCredential::class, 'team_credential_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
