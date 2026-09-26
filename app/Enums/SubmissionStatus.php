<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case Pending = 'pending';
    case Submitted = 'submitted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('En cours'),
            self::Submitted => __('Rendu'),
        };
    }
}
