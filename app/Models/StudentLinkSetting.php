<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLinkSetting extends Model
{
    protected $table = 'studentlink_settings';

    protected $fillable = [
        'require_registration_domain',
    ];

    protected function casts(): array
    {
        return [
            'require_registration_domain' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            [],
            ['require_registration_domain' => true],
        );
    }
}
