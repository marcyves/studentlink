<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StudentLinkSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = StudentLinkSetting::current();

        return Inertia::render('Admin/Settings', [
            'settings' => [
                'require_registration_domain' => $settings->require_registration_domain,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'require_registration_domain' => ['required', 'boolean'],
        ]);

        StudentLinkSetting::current()->update($validated);

        return back()->with('success', 'Paramètres enregistrés.');
    }
}
