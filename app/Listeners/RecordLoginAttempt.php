<?php

namespace App\Listeners;

use App\Enums\LoginOutcome;
use App\Models\User;
use App\Services\LoginAttemptRecorder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;

class RecordLoginAttempt
{
    public function __construct(private LoginAttemptRecorder $recorder) {}

    public function successful(Login $event): void
    {
        $user = $event->user instanceof User ? $event->user : null;

        $this->recorder->record(
            request(),
            $user?->email ?? '',
            LoginOutcome::Success,
            $user,
        );
    }

    public function failed(Failed $event): void
    {
        $this->recorder->record(
            request(),
            $this->credential($event->credentials),
            LoginOutcome::Blocked,
        );
    }

    public function lockedOut(Lockout $event): void
    {
        $this->recorder->record(
            $event->request,
            $this->credential($event->request->all()),
            LoginOutcome::Blocked,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function credential(array $payload): string
    {
        foreach (['email', 'username'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) || is_numeric($value)) {
                return (string) $value;
            }
        }

        return '';
    }
}
