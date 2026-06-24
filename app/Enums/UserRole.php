<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Professor = 'professor';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Étudiant',
            self::Professor => 'Professeur',
        };
    }
}
