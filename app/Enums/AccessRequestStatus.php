<?php

namespace App\Enums;

enum AccessRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('En attente'),
            self::Accepted => __('Acceptée'),
            self::Rejected => __('Rejetée'),
        };
    }
}
