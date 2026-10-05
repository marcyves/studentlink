<?php

namespace App\Http\Controllers\Professor;

use App\Enums\LoginOutcome;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseActivity;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CourseActivityController extends Controller
{
    public function show(Course $course): Response
    {
        abort_unless($course->professor_id === auth()->id(), 403);

        $students = $course->students()->orderBy('name')->get();
        $lastLogins = $this->lastSuccessfulLogins($students->pluck('id'));

        $activities = CourseActivity::query()
            ->where('course_id', $course->id)
            ->with('user')
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (CourseActivity $activity) => [
                'id' => $activity->id,
                'student' => $activity->user?->name,
                'action' => $activity->action->value,
                'action_label' => $activity->action->label($activity->subject),
                'occurred_at' => $this->formatWhen($activity->created_at),
            ]);

        return Inertia::render('Professor/CourseActivity', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'code' => $course->code,
            ],
            'students' => $students->map(function (User $student) use ($lastLogins) {
                $lastLogin = $lastLogins->get($student->id);

                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'email' => $student->email,
                    'has_logged_in' => $lastLogin instanceof Carbon,
                    'last_login_at' => $lastLogin instanceof Carbon ? $this->formatWhen($lastLogin) : null,
                ];
            })->values(),
            'activities' => $activities,
        ]);
    }

    /**
     * @param  Collection<int, int>  $studentIds
     * @return Collection<int, Carbon>
     */
    private function lastSuccessfulLogins(Collection $studentIds): Collection
    {
        if ($studentIds->isEmpty()) {
            return collect();
        }

        return LoginAttempt::query()
            ->select('user_id')
            ->selectRaw('MAX(created_at) as last_login_at')
            ->where('outcome', LoginOutcome::Success)
            ->whereIn('user_id', $studentIds)
            ->groupBy('user_id')
            ->get()
            ->mapWithKeys(fn (LoginAttempt $attempt) => [
                (int) $attempt->user_id => Carbon::parse($attempt->getAttribute('last_login_at')),
            ]);
    }

    private function formatWhen(Carbon $value): string
    {
        return $value->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }
}
