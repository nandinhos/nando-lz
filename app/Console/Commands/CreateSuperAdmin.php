<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Bootstrap do primeiro superadmin (PRD §5.3).
 *
 * - Bloqueia criação duplicada: só roda enquanto não existir nenhum usuário.
 * - Argumentos (--name/--email/--password) só são aceitos em local/dev;
 *   fora disso o comando é sempre interativo.
 * - Fora de local exige senha forte (recusa senhas triviais).
 * - O usuário criado acessa o painel ops (e os demais) — sem regra de negócio.
 */
#[Signature('superadmin:create {--name=} {--email=} {--password=}')]
#[Description('Cria o primeiro superadmin (bootstrap). Bloqueia duplicidade.')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        if (User::query()->exists()) {
            $this->error('Já existe um usuário. O primeiro superadmin só pode ser criado uma vez — crie os demais pelo painel.');

            return self::FAILURE;
        }

        // Argumentos só valem em local/dev (§5.3); fora disso, sempre interativo.
        $argsAllowed = $this->getLaravel()->environment('local', 'dev');

        [$name, $email, $password] = $argsAllowed
            ? [$this->option('name'), $this->option('email'), $this->option('password')]
            : [null, null, null];

        if (! $argsAllowed && ($this->option('name') || $this->option('email') || $this->option('password'))) {
            $this->warn('Argumentos ignorados fora de local/dev — usando modo interativo.');
        }

        $name = $name ?: $this->ask('Nome');
        $email = $email ?: $this->ask('E-mail');

        if (! $password) {
            $password = $this->secret('Senha');
            if ($password !== $this->secret('Confirme a senha')) {
                $this->error('As senhas não conferem.');

                return self::FAILURE;
            }
        }

        // Senhas triviais só passam no ambiente local (§5.3); fora dele, senha forte.
        $passwordRule = $this->getLaravel()->environment('local')
            ? Password::min(8)
            : Password::min(12)->mixedCase()->numbers()->symbols();

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'min:2', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', $passwordRule],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
        // Fora do fillable de propósito — atribuição explícita (mass assignment a descartaria).
        $user->email_verified_at = now();
        $user->save();

        $this->info("Superadmin criado: {$user->email} — acesse /ops para entrar.");

        return self::SUCCESS;
    }
}
