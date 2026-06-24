<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();

        if ($user->role === UserRole::Professor) {
            return app(Professor\DashboardController::class)->index();
        }

        return app(Student\DashboardController::class)->index();
    }
}
