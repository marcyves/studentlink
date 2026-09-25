<?php

namespace App\Http\Controllers\Student;

use App\Enums\DeliverableType;
use App\Enums\PeerEvaluationStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Group;
use App\Models\PeerEvaluation;
use App\Models\Project;
use App\Services\EmailDomainService;
use App\Support\DeliverablePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private EmailDomainService $emailDomains,
        private DeliverablePresenter $deliverables,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();

        $groups = $user->groups()
            ->with([
                'project.course',
                'members',
                'submission',
            ])
            ->get()
            ->map(fn (Group $group) => $this->formatGroup($group));

        $enrolledCourses = $user->courses()
            ->with(['projects' => fn ($q) => $q->orderBy('ends_at')])
            ->get()
            ->map(fn (Course $course) => [
                'id' => $course->id,
                'title' => $course->title,
                'code' => $course->code,
                'projects' => $course->projects->map(fn (Project $project) => [
                    'id' => $project->id,
                    'title' => $project->title,
                    'ends_at' => $project->ends_at?->toIso8601String(),
                ]),
            ]);

        return Inertia::render('Student/Dashboard', [
            'groups' => $groups,
            'enrolledCourses' => $enrolledCourses,
            'stats' => [
                'groups' => $groups->count(),
                'pendingEvaluations' => PeerEvaluation::query()
                    ->where('reviewer_id', $user->id)
                    ->where('status', PeerEvaluationStatus::Pending)
                    ->count(),
            ],
        ]);
    }

    public function joinCourse(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'join_code' => ['required', 'string', 'max:12'],
        ]);

        $course = Course::query()
            ->where('join_code', strtoupper(trim($validated['join_code'])))
            ->firstOrFail();

        $this->ensureEmailAllowedForCourse($request->user()->email, $course);

        $request->user()->courses()->syncWithoutDetaching([$course->id]);

        return back()->with('success', "Inscrit au cours « {$course->title} ».");
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $project = Project::with('course')->findOrFail($validated['project_id']);

        if (! $user->courses()->where('courses.id', $project->course_id)->exists()) {
            abort(403, 'Inscrivez-vous au cours avant de créer un groupe.');
        }

        DB::transaction(function () use ($user, $project, $validated) {
            $group = Group::create([
                'project_id' => $project->id,
                'created_by' => $user->id,
                'name' => $validated['name'],
            ]);

            $group->members()->attach($user->id, ['is_leader' => true]);
            $group->submission()->create(['status' => 'pending']);
        });

        return back()->with('success', 'Groupe créé.');
    }

    public function joinGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'max:10'],
        ]);

        $group = Group::query()
            ->with('project.course')
            ->where('invite_code', strtoupper(trim($validated['invite_code'])))
            ->firstOrFail();

        $user = $request->user();
        $course = $group->project->course;

        if (! $user->courses()->where('courses.id', $course->id)->exists()) {
            $this->ensureEmailAllowedForCourse($user->email, $course);
            $user->courses()->attach($course->id);
        }

        $group->members()->syncWithoutDetaching([$user->id => ['is_leader' => false]]);

        if (! $group->submission) {
            $group->submission()->create(['status' => 'pending']);
        }

        return back()->with('success', "Vous avez rejoint « {$group->name} ».");
    }

    private function ensureEmailAllowedForCourse(string $email, Course $course): void
    {
        if ($this->emailDomains->isAllowedForCourse($email, $course)) {
            return;
        }

        $domains = collect($this->emailDomains->effectiveDomainsForCourse($course))
            ->map(fn (string $domain) => '@'.$domain)
            ->implode(', ');

        throw ValidationException::withMessages([
            'join_code' => "Adresse non autorisée pour ce cours. Domaines acceptés : {$domains}.",
        ]);
    }

    private function formatGroup(Group $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'invite_code' => $group->invite_code,
            'project' => [
                'id' => $group->project->id,
                'title' => $group->project->title,
                'course' => $group->project->course->title,
                'deliverable_type' => ($group->project->deliverable_type ?? DeliverableType::None)->value,
                'deliverable_label' => ($group->project->deliverable_type ?? DeliverableType::None)->label(),
            ],
            'members_count' => $group->members->count(),
            'submission' => $group->submission ? [
                'status' => $group->submission->status->value,
                'label' => $group->submission->status->label(),
            ] : null,
            'deliverable' => $this->deliverables->present($group->project, $group->submission),
        ];
    }
}
