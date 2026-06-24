<?php

namespace App\Enums;

enum PeerEvaluationStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À faire',
            self::Completed => 'Terminée',
        };
    }
}
