<?php

namespace App\Support;

class PeerScorePool
{
    /**
     * Points a student may distribute for one criterion across one set of targets.
     * The pool is the target count times floor(max / 2), and at least 1.
     */
    public static function size(int $targetCount, int $maxScore): int
    {
        return max(1, $targetCount * intdiv(max(0, $maxScore), 2));
    }
}
