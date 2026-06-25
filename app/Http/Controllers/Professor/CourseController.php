<?php

namespace App\Http\Controllers\Professor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EmailDomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    public function __construct(
        private EmailDomainService $emailDomains,
    ) {}

    public function updateDomains(Request $request, Course $course): RedirectResponse
    {
        if ($course->professor_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'allowed_email_domains' => ['nullable', 'array'],
            'allowed_email_domains.*' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/i',
            ],
        ]);

        $domains = $this->emailDomains->normalizeDomains(
            $validated['allowed_email_domains'] ?? [],
        );

        $course->update([
            'allowed_email_domains' => $domains === [] ? null : $domains,
        ]);

        return back()->with('success', 'Domaines e-mail mis à jour.');
    }
}
