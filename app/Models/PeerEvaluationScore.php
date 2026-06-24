<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerEvaluationScore extends Model
{
    protected $fillable = [
        'peer_evaluation_id',
        'rubric_criterion_id',
        'score',
    ];

    public function peerEvaluation(): BelongsTo
    {
        return $this->belongsTo(PeerEvaluation::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(RubricCriterion::class, 'rubric_criterion_id');
    }
}
