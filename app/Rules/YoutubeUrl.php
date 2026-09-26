<?php

namespace App\Rules;

use App\Support\YoutubeUrl as Youtube;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YoutubeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || Youtube::id($value) === null) {
            $fail(__('Indiquez une URL YouTube valide.'));
        }
    }
}
