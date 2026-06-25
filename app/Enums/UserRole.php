<?php

namespace App\Enums;

enum UserRole: string
{
    case Student = 'student';
    case Professor = 'professor';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Étudiant',
            self::Professor => 'Professeur',
            self::Admin => 'Administrateur',
        };
    }
}
