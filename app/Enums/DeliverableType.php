<?php

namespace App\Enums;

enum DeliverableType: string
{
    case None = 'none';
    case File = 'file';
    case Link = 'link';
    case Image = 'image';
    case Video = 'video';
    case Youtube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Rien',
            self::File => 'Fichier',
            self::Link => 'Lien',
            self::Image => 'Image',
            self::Video => 'Vidéo',
            self::Youtube => 'Vidéo YouTube',
        };
    }

    public function storesFile(): bool
    {
        return match ($this) {
            self::File, self::Image, self::Video => true,
            default => false,
        };
    }

    public function storesUrl(): bool
    {
        return match ($this) {
            self::Link, self::Youtube => true,
            default => false,
        };
    }

    /**
     * Maximum upload size in kilobytes. Null when the type has no file.
     */
    public function maxKilobytes(): ?int
    {
        return match ($this) {
            self::File => 10 * 1024,
            self::Image => 8 * 1024,
            self::Video => 50 * 1024,
            default => null,
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases(),
        );
    }
}
