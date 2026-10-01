<?php

namespace App\Enums;

enum LoginOutcome: string
{
    case Success = 'success';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Success => __('Réussi'),
            self::Blocked => __('Bloqué'),
        };
    }
}
