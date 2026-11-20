<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

#[Signature('mynet:make-administrator {name?} {email?}')]
#[Description('Buat akun Administrator pertama untuk aplikasi mynet.')]
class CreateAdministrator extends Command
{
    public function handle(): int
    {
        $name = trim((string) ($this->argument('name') ?: $this->ask('Nama administrator')));
        $email = strtolower(trim((string) ($this->argument('email') ?: $this->ask('Email administrator'))));

        try {
            Validator::make(compact('name', 'email'), [
                'name' => ['required', 'string', 'max:160'],
                'email' => ['required', 'email', 'max:180', 'unique:users,email'],
            ])->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Kata sandi administrator (minimal 12 karakter)');
        $confirmation = (string) $this->secret('Ulangi kata sandi');

        if (strlen($password) < 12 || ! hash_equals($password, $confirmation)) {
            $this->error('Kata sandi tidak cocok atau kurang dari 12 karakter.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'full_name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => 'administrator',
            'active' => true,
            'force_password_change' => true,
            'branch_id' => null,
        ]);

        $this->info('Akun Administrator dibuat. Masuk lalu ubah kata sandi dan verifikasi email melalui OTP.');

        return self::SUCCESS;
    }
}
