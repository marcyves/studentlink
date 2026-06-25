<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailDomainService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function __construct(
        private EmailDomainService $emailDomains,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'registrationHint' => $this->emailDomains->registrationHint(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! $this->emailDomains->isAllowedForRegistration($request->email)) {
            $hint = $this->emailDomains->registrationHint();

            throw ValidationException::withMessages([
                'email' => $hint
                    ? "Utilisez une adresse institutionnelle ({$hint})."
                    : 'Inscription réservée aux adresses e-mail autorisées par votre établissement.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => UserRole::Student,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
