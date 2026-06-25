<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user->role === UserRole::Admin) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->role === UserRole::Professor) {
            return app(Professor\DashboardController::class)->index();
        }

        return app(Student\DashboardController::class)->index();
    }
}
