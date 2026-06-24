<?php

namespace App\Models;

use App\Enums\EvaluationType;
use App\Enums\PeerEvaluationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeerEvaluation extends Model
{
    protected $fillable = [
        'project_id',
        'reviewer_id',
        'type',
        'reviewee_group_id',
        'reviewee_user_id',
        'status',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => EvaluationType::class,
            'status' => PeerEvaluationStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === PeerEvaluationStatus::Pending;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function revieweeGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'reviewee_group_id');
    }

    public function revieweeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_user_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(PeerEvaluationScore::class);
    }
}
