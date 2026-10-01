<?php

namespace App\Models;

use App\Enums\LoginOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'identifier',
        'ip_address',
        'country',
        'city',
        'outcome',
    ];

    protected function casts(): array
    {
        return [
            'outcome' => LoginOutcome::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
