<?php

namespace App\Services;

use App\Enums\LoginOutcome;
use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Http\Request;

class LoginAttemptRecorder
{
    public function __construct(private IpGeolocator $geolocator) {}

    public function record(Request $request, string $identifier, LoginOutcome $outcome, ?User $user = null): LoginAttempt
    {
        $ip = $request->ip();
        $place = $this->geolocator->locate(is_string($ip) ? $ip : null);

        return LoginAttempt::query()->create([
            'user_id' => $outcome === LoginOutcome::Success ? $user?->id : null,
            'identifier' => mb_substr(trim($identifier), 0, 255),
            'ip_address' => is_string($ip) && $ip !== '' ? mb_substr($ip, 0, 45) : null,
            'country' => $place['country'],
            'city' => $place['city'],
            'outcome' => $outcome,
        ]);
    }
}
