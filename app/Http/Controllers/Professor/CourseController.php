<?php

namespace App\Http\Controllers\Professor;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\EmailDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function __construct(
        private EmailDomainService $emailDomains,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
            'join_code' => strtoupper(trim((string) $request->input('join_code', ''))),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'code' => ['required', 'string', 'max:32', 'alpha_dash', 'unique:courses,code'],
            'join_code' => ['required', 'string', 'max:12', 'alpha_dash', 'unique:courses,join_code'],
            'allowed_email_domains' => ['prohibited'],
        ]);

        Course::create([
            'professor_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'],
            'join_code' => $validated['join_code'],
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Cours créé.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->ensureOwner($request, $course);

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
            'join_code' => strtoupper(trim((string) $request->input('join_code', ''))),
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'code' => [
                'required',
                'string',
                'max:32',
                'alpha_dash',
                Rule::unique('courses', 'code')->ignore($course->id),
            ],
            'join_code' => [
                'required',
                'string',
                'max:12',
                'alpha_dash',
                Rule::unique('courses', 'join_code')->ignore($course->id),
            ],
        ]);

        $course->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'],
            'join_code' => $validated['join_code'],
        ]);

        return back()->with('success', 'Cours mis à jour.');
    }

    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $this->ensureOwner($request, $course);

        if ($course->projects()->exists()) {
            return back()->with('error', 'Supprimez d\'abord les projets de ce cours.');
        }

        $validated = $request->validate([
            'delete_students' => ['required', 'boolean'],
        ], [
            'delete_students.required' => 'Indiquez si les étudiants doivent aussi être effacés.',
        ]);

        $removedStudents = false;

        DB::transaction(function () use ($course, $validated, &$removedStudents) {
            if ($validated['delete_students']) {
                $studentIds = $this->studentsSafeToDelete($course);

                if ($studentIds !== []) {
                    User::query()->whereIn('id', $studentIds)->delete();
                    $removedStudents = true;
                }
            }

            $course->delete();
        });

        return back()->with(
            'success',
            $removedStudents
                ? 'Cours effacé. Les étudiants qui n\'étaient inscrits qu\'à ce cours ont aussi été effacés.'
                : 'Cours effacé.',
        );
    }

    public function updateDomains(Request $request, Course $course): RedirectResponse
    {
        if ($course->professor_id !== $request->user()->id) {
            abort(403);
        }

        $domains = $request->input('allowed_email_domains');

        $request->merge([
            'allowed_email_domains' => is_array($domains)
                ? array_values(array_filter(array_map(
                    fn ($domain) => is_string($domain) ? ltrim(trim($domain), '@') : $domain,
                    $domains,
                ), fn ($domain) => $domain !== ''))
                : $domains,
        ]);

        $validated = $request->validate($this->domainRules());

        $course->update([
            'allowed_email_domains' => $this->normalizedDomains(
                $validated['allowed_email_domains'] ?? [],
            ),
        ]);

        return back()->with('success', 'Domaines e-mail mis à jour.');
    }

    /**
     * @return array<string, mixed>
     */
    private function domainRules(): array
    {
        return [
            'allowed_email_domains' => ['nullable', 'array'],
            'allowed_email_domains.*' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/i',
            ],
        ];
    }

    /**
     * @param  array<int, string>  $domains
     * @return array<int, string>|null
     */
    private function normalizedDomains(array $domains): ?array
    {
        $normalized = $this->emailDomains->normalizeDomains($domains);

        return $normalized === [] ? null : $normalized;
    }

    private function ensureOwner(Request $request, Course $course): void
    {
        if ($course->professor_id !== $request->user()->id) {
            abort(403);
        }
    }

    /**
     * Students enrolled only in this course, with no group or evaluation elsewhere.
     *
     * @return array<int, int>
     */
    private function studentsSafeToDelete(Course $course): array
    {
        return $course->students()
            ->where('users.role', UserRole::Student->value)
            ->whereDoesntHave('courses', fn ($query) => $query->whereKeyNot($course->id))
            ->whereDoesntHave('groups')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('peer_evaluations')
                    ->whereColumn('peer_evaluations.reviewer_id', 'users.id');
            })
            ->pluck('users.id')
            ->all();
    }
}
