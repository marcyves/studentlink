<?php

namespace App\Services;

use App\Models\Course;
use App\Models\StudentLinkSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EmailDomainService
{
    public function requiresRegistrationDomain(): bool
    {
        return StudentLinkSetting::current()->require_registration_domain;
    }

    public function extractDomain(string $email): string
    {
        return Str::lower(Str::after($email, '@'));
    }

    public function normalizeDomain(string $domain): string
    {
        return Str::lower(ltrim(trim($domain), '@'));
    }

    /**
     * @param  array<int, string>|null  $domains
     * @return array<int, string>
     */
    public function normalizeDomains(?array $domains): array
    {
        if ($domains === null) {
            return [];
        }

        return collect($domains)
            ->map(fn (string $domain) => $this->normalizeDomain($domain))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function professorDefaultDomain(User $professor): ?string
    {
        $domain = $this->extractDomain($professor->email);

        return $domain !== '' ? $domain : null;
    }

    /**
     * @return array<int, string>
     */
    public function effectiveDomainsForCourse(Course $course): array
    {
        $explicit = $this->normalizeDomains($course->allowed_email_domains);

        if ($explicit !== []) {
            return $explicit;
        }

        $course->loadMissing('professor');

        if ($course->professor) {
            $default = $this->professorDefaultDomain($course->professor);

            if ($default !== null) {
                return [$default];
            }
        }

        return [];
    }

    public function emailMatchesDomains(string $email, array $domains): bool
    {
        $domains = $this->normalizeDomains($domains);

        if ($domains === []) {
            return true;
        }

        $emailDomain = $this->extractDomain($email);

        return in_array($emailDomain, $domains, true);
    }

    public function isAllowedForCourse(string $email, Course $course): bool
    {
        if (! $this->requiresRegistrationDomain()) {
            return true;
        }

        return $this->emailMatchesDomains($email, $this->effectiveDomainsForCourse($course));
    }

    public function isAllowedForRegistration(string $email): bool
    {
        if (! $this->requiresRegistrationDomain()) {
            return true;
        }

        $configuredDomains = $this->registrationDomains();

        if ($configuredDomains->isEmpty()) {
            return false;
        }

        return $this->emailMatchesDomains($email, $configuredDomains->all());
    }

    /**
     * @return Collection<int, string>
     */
    public function registrationDomains(): Collection
    {
        if (! $this->requiresRegistrationDomain()) {
            return collect();
        }

        return Course::query()
            ->with('professor')
            ->get()
            ->flatMap(fn (Course $course) => $this->effectiveDomainsForCourse($course))
            ->unique()
            ->values();
    }

    public function registrationHint(): ?string
    {
        if (! $this->requiresRegistrationDomain()) {
            return null;
        }

        $domains = $this->registrationDomains();

        if ($domains->isEmpty()) {
            return null;
        }

        return $domains->map(fn (string $domain) => '@'.$domain)->implode(', ');
    }
}
