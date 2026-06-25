<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use App\Models\Project;
use App\Services\EmailDomainService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private EmailDomainService $emailDomains,
    ) {}

    public function index(): Response
    {
        $professor = auth()->user();

        $courses = Course::query()
            ->where('professor_id', $professor->id)
            ->with([
                'projects.groups.members',
                'projects.groups.submission',
                'projects.rubric.criteria',
            ])
            ->get()
            ->map(function (Course $course) {
                $groups = $course->projects->flatMap->groups;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'code' => $course->code,
                    'join_code' => $course->join_code,
                    'allowed_email_domains' => $course->allowed_email_domains ?? [],
                    'effective_email_domains' => $this->emailDomains
                        ->effectiveDomainsForCourse($course),
                    'default_professor_domain' => $this->emailDomains
                        ->professorDefaultDomain($professor),
                    'students_count' => $course->students()->count(),
                    'projects' => $course->projects->map(fn (Project $project) => [
                        'id' => $project->id,
                        'title' => $project->title,
                        'ends_at' => $project->ends_at?->format('d/m/Y'),
                        'groups_count' => $project->groups->count(),
                        'rubric' => $project->rubric ? [
                            'id' => $project->rubric->id,
                            'name' => $project->rubric->name,
                            'criteria_count' => $project->rubric->criteria->count(),
                        ] : null,
                        'groups' => $project->groups->map(fn (Group $group) => [
                            'id' => $group->id,
                            'name' => $group->name,
                            'members_count' => $group->members->count(),
                            'submission_status' => $group->submission?->status->value,
                            'submission_label' => $group->submission?->status->label(),
                        ]),
                    ]),
                    'stats' => [
                        'groups' => $groups->count(),
                        'submitted' => $groups->filter(
                            fn (Group $g) => $g->submission?->status->value === 'submitted'
                        )->count(),
                    ],
                ];
            });

        return Inertia::render('Professor/Dashboard', [
            'courses' => $courses,
        ]);
    }
}
