<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EmailDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function __construct(
        private EmailDomainService $emailDomains,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $domains = $request->input('allowed_email_domains');

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code', ''))),
            'join_code' => strtoupper(trim((string) $request->input('join_code', ''))),
            'allowed_email_domains' => is_array($domains)
                ? array_values(array_filter(array_map(
                    fn ($domain) => is_string($domain) ? ltrim(trim($domain), '@') : $domain,
                    $domains,
                ), fn ($domain) => $domain !== ''))
                : $domains,
        ]);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'code' => ['required', 'string', 'max:32', 'alpha_dash', 'unique:courses,code'],
            'join_code' => ['required', 'string', 'max:12', 'alpha_dash', 'unique:courses,join_code'],
            ...$this->domainRules(),
        ]);

        $domains = $this->normalizedDomains($validated['allowed_email_domains'] ?? []);

        Course::create([
            'professor_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'],
            'join_code' => $validated['join_code'],
            'allowed_email_domains' => $domains,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Cours créé.');
    }

    public function updateDomains(Request $request, Course $course): RedirectResponse
    {
        if ($course->professor_id !== $request->user()->id) {
            abort(403);
        }

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
}
