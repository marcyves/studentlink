<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccessRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ProfessorAccessRequest;
use App\Services\ProfessorProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class AccessRequestController extends Controller
{
    public function __construct(
        private ProfessorProvisioningService $provisioning,
    ) {}

    public function accept(ProfessorAccessRequest $accessRequest): RedirectResponse
    {
        $user = $this->provisioning->accept($accessRequest);

        Password::sendResetLink(['email' => $user->email]);

        return back()->with(
            'success',
            __('Demande acceptée — compte professeur créé pour :name. Un e-mail de définition de mot de passe a été envoyé.', [
                'name' => $user->name,
            ]),
        );
    }

    public function reject(ProfessorAccessRequest $accessRequest): RedirectResponse
    {
        $this->provisioning->reject($accessRequest);

        return back()->with(
            'success',
            __('Demande de :name rejetée.', ['name' => $accessRequest->name]),
        );
    }
}
