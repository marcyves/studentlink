<?php

namespace App\Services;

use App\Enums\AccessRequestStatus;
use App\Enums\UserRole;
use App\Models\ProfessorAccessRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfessorProvisioningService
{
    public function provisionFromRequest(ProfessorAccessRequest $accessRequest): User
    {
        $email = Str::lower($accessRequest->email);
        $existing = User::query()->where('email', $email)->first();

        if ($existing) {
            if ($existing->isProfessor()) {
                return $existing;
            }

            throw ValidationException::withMessages([
                'email' => 'Un compte étudiant existe déjà avec cette adresse e-mail.',
            ]);
        }

        return User::create([
            'name' => $accessRequest->name,
            'email' => $email,
            'password' => Hash::make(Str::password(16)),
            'role' => UserRole::Professor,
            'email_verified_at' => now(),
        ]);
    }

    public function accept(ProfessorAccessRequest $accessRequest): User
    {
        if ($accessRequest->status !== AccessRequestStatus::Pending->value) {
            throw ValidationException::withMessages([
                'request' => 'Cette demande a déjà été traitée.',
            ]);
        }

        $user = $this->provisionFromRequest($accessRequest);

        $accessRequest->update([
            'status' => AccessRequestStatus::Accepted->value,
        ]);

        return $user;
    }

    public function reject(ProfessorAccessRequest $accessRequest): void
    {
        if ($accessRequest->status !== AccessRequestStatus::Pending->value) {
            throw ValidationException::withMessages([
                'request' => 'Cette demande a déjà été traitée.',
            ]);
        }

        $accessRequest->update([
            'status' => AccessRequestStatus::Rejected->value,
        ]);
    }
}
