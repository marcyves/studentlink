<?php

namespace App\Enums;

enum InterfaceLocale: string
{
    case French = 'fr';
    case English = 'en';
    case Italian = 'it';
    case Spanish = 'es';

    public function nativeLabel(): string
    {
        return match ($this) {
            self::French => 'Français',
            self::English => 'English',
            self::Italian => 'Italiano',
            self::Spanish => 'Español',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $locale) => [
                'value' => $locale->value,
                'label' => $locale->nativeLabel(),
            ],
            self::cases(),
        );
    }
}
