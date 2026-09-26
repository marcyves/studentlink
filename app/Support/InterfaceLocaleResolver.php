<?php

namespace App\Support;

use App\Enums\InterfaceLocale;
use App\Models\User;

class InterfaceLocaleResolver
{
    public function for(?User $user): string
    {
        if ($user === null || $user->isAdmin()) {
            return InterfaceLocale::French->value;
        }

        if ($user->isProfessor()) {
            return $this->normalize($user->locale);
        }

        $locale = $user->courses()
            ->join('users as professors', 'professors.id', '=', 'courses.professor_id')
            ->orderByDesc('course_user.created_at')
            ->orderByDesc('courses.id')
            ->value('professors.locale');

        return $this->normalize($locale);
    }

    private function normalize(mixed $locale): string
    {
        $value = $locale instanceof InterfaceLocale ? $locale->value : (string) $locale;

        return InterfaceLocale::tryFrom($value)?->value ?? InterfaceLocale::French->value;
    }
}
