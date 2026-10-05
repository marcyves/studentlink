<?php

namespace App\Services;

use App\Enums\CourseActivityAction;
use App\Models\Course;
use App\Models\CourseActivity;
use App\Models\User;

class CourseActivityRecorder
{
    public function record(User $student, Course $course, CourseActivityAction $action, ?string $subject = null): CourseActivity
    {
        $subject = is_string($subject) ? trim($subject) : '';

        return CourseActivity::query()->create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'action' => $action,
            'subject' => $subject !== '' ? mb_substr($subject, 0, 255) : null,
        ]);
    }
}
