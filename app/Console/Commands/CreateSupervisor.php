<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

#[Signature('app:create-supervisor')]
#[Description('Crée un compte superviseur de manière interactive')]
class CreateSupervisor extends Command
{
    public function handle(): int
    {
        $this->info('Création d’un compte superviseur.');
        $this->line('Aucune inscription publique n’est disponible : ce compte permet d’administrer la plateforme.');

        $name = trim((string) $this->ask('Nom complet'));
        $email = mb_strtolower(trim((string) $this->ask('Adresse e-mail')));

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Mot de passe (8 caractères minimum)');
        $confirmation = (string) $this->secret('Confirmez le mot de passe');

        if ($password !== $confirmation) {
            $this->error('Les mots de passe ne correspondent pas.');

            return self::FAILURE;
        }

        if (mb_strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        $supervisor = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Supervisor,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->newLine();
        $this->info('Compte superviseur créé avec succès.');
        $this->line('Nom : '.$supervisor->name);
        $this->line('E-mail : '.$supervisor->email);

        return self::SUCCESS;
    }
}
