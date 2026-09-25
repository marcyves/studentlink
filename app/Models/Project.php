<?php

namespace App\Models;

use App\Enums\DeliverableType;
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
        'deliverable_type',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'deliverable_type' => DeliverableType::class,
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
