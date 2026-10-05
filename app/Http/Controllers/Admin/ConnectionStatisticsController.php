<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LoginOutcome;
use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionStatisticsController extends Controller
{
    public function index(): Response
    {
        $attempts = LoginAttempt::query()
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (LoginAttempt $attempt) => [
                'id' => $attempt->id,
                'identifier' => $attempt->identifier,
                'ip_address' => $attempt->ip_address,
                'country' => $attempt->country,
                'city' => $attempt->city,
                'outcome' => $attempt->outcome->value,
                'outcome_label' => $attempt->outcome->label(),
                'occurred_at' => $this->formatWhen($attempt->created_at),
            ]);

        return Inertia::render('Admin/Connections', [
            'attempts' => $attempts,
            'chart' => [
                'success' => LoginAttempt::query()->where('outcome', LoginOutcome::Success)->count(),
                'blocked' => LoginAttempt::query()->where('outcome', LoginOutcome::Blocked)->count(),
            ],
        ]);
    }

    private function formatWhen(Carbon $value): string
    {
        return $value->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }
}
