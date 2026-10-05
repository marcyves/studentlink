<?php

namespace App\Models;

use App\Enums\CourseActivityAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseActivity extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'action',
        'subject',
    ];

    protected function casts(): array
    {
        return [
            'action' => CourseActivityAction::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
