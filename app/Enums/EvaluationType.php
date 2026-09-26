<?php

namespace App\Enums;

enum EvaluationType: string
{
    case Inter = 'inter';
    case Intra = 'intra';

    public function label(): string
    {
        return match ($this) {
            self::Inter => __('Inter-groupe'),
            self::Intra => __('Intra-groupe'),
        };
    }
}
