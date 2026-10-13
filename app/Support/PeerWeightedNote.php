<?php

namespace App\Support;

use App\Models\PeerEvaluation;

class PeerWeightedNote
{
    /**
     * Note on a 100-point scale: each scored criterion contributes
     * (score / max) × weight. Weights of a saved rubric sum to 100,
     * so a fully scored evaluation at the maximum is 100.
     * A criterion with no score contributes 0.
     */
    public static function of(PeerEvaluation $evaluation): ?float
    {
        $total = 0.0;
        $used = 0;

        foreach ($evaluation->scores as $score) {
            $criterion = $score->criterion;

            if ($criterion === null || (int) $criterion->max_score < 1) {
                continue;
            }

            $total += ($score->score / $criterion->max_score) * $criterion->weight;
            $used++;
        }

        return $used === 0 ? null : $total;
    }
}
