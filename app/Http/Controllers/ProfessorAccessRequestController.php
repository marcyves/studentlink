<?php

namespace App\Http\Controllers;

use App\Mail\ProfessorAccessRequestMail;
use App\Models\ProfessorAccessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ProfessorAccessRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $pending = ProfessorAccessRequest::query()
            ->where('email', $validated['email'])
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return back()->with('success', __('Votre demande est déjà en cours de traitement.'));
        }

        $accessRequest = ProfessorAccessRequest::create($validated);

        Mail::to(config('studentlink.admin_email'))
            ->send(new ProfessorAccessRequestMail($accessRequest));

        return back()->with(
            'success',
            __('Demande envoyée. Un administrateur vous contactera par e-mail.'),
        );
    }
}
