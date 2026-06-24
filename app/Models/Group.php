<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Group extends Model
{
    protected $fillable = [
        'project_id',
        'created_by',
        'name',
        'invite_code',
    ];

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->invite_code)) {
                $group->invite_code = strtoupper(Str::random(8));
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_leader')
            ->withTimestamps();
    }

    public function submission(): HasOne
    {
        return $this->hasOne(Submission::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }
}
