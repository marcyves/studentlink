<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function rubric(): HasOne
    {
        return $this->hasOne(Rubric::class);
    }

    public function peerEvaluations(): HasMany
    {
        return $this->hasMany(PeerEvaluation::class);
    }
}
