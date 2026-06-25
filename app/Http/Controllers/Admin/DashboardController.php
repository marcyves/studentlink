<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\AccessRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ProfessorAccessRequest;
use App\Models\User;
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
        $professors = User::query()
            ->where('role', UserRole::Professor)
            ->with([
                'taughtCourses' => fn ($query) => $query->with('students')->orderBy('title'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $professor) {
                $students = $professor->taughtCourses
                    ->flatMap(fn ($course) => $course->students->map(fn ($student) => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                        'course' => $course->title,
                    ]))
                    ->unique('id')
                    ->values();

                return [
                    'id' => $professor->id,
                    'name' => $professor->name,
                    'email' => $professor->email,
                    'default_domain' => $this->emailDomains->professorDefaultDomain($professor),
                    'courses_count' => $professor->taughtCourses->count(),
                    'students_count' => $students->count(),
                    'courses' => $professor->taughtCourses->map(fn ($course) => [
                        'id' => $course->id,
                        'title' => $course->title,
                        'code' => $course->code,
                        'join_code' => $course->join_code,
                        'allowed_email_domains' => $course->allowed_email_domains ?? [],
                        'effective_domains' => $this->emailDomains->effectiveDomainsForCourse($course),
                        'students_count' => $course->students->count(),
                    ]),
                    'students' => $students,
                ];
            });

        $accessRequests = ProfessorAccessRequest::query()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ProfessorAccessRequest $request) => [
                'id' => $request->id,
                'name' => $request->name,
                'email' => $request->email,
                'institution' => $request->institution,
                'message' => $request->message,
                'status' => $request->status,
                'status_label' => AccessRequestStatus::from($request->status)->label(),
                'is_pending' => $request->status === AccessRequestStatus::Pending->value,
                'created_at' => $request->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'professors' => $professors,
            'accessRequests' => $accessRequests,
            'adminEmail' => config('studentlink.admin_email'),
        ]);
    }
}
