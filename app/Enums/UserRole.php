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
            self::Student => __('Étudiant'),
            self::Professor => __('Professeur'),
            self::Admin => __('Administrateur'),
        };
    }
}
