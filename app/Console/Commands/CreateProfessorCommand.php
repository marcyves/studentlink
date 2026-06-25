<?php

namespace App\Console\Commands;

use App\Models\ProfessorAccessRequest;
use App\Models\User;
use App\Services\ProfessorProvisioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateProfessorCommand extends Command
{
    protected $signature = 'studentlink:create-professor {email} {name?}';

    protected $description = 'Create a professor account (admin only workflow)';

    public function handle(ProfessorProvisioningService $provisioning): int
    {
        $email = Str::lower($this->argument('email'));
        $name = $this->argument('name') ?? Str::before($email, '@');

        if (User::query()->where('email', $email)->exists()) {
            $this->error("Un compte existe déjà pour {$email}.");

            return self::FAILURE;
        }

        $request = ProfessorAccessRequest::make([
            'name' => $name,
            'email' => $email,
            'status' => 'pending',
        ]);

        $user = $provisioning->provisionFromRequest($request);

        $this->info("Professeur créé : {$user->name} <{$user->email}>");

        return self::SUCCESS;
    }
}
