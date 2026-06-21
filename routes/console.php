<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user:create-super-admin {--name=} {--email=}', function () {
    $name = $this->option('name') ?: $this->ask('Nama super admin');
    $email = $this->option('email') ?: $this->ask('Email super admin');
    $password = $this->secret('Password (minimal 12 karakter, huruf besar/kecil, angka, dan simbol)');
    $passwordConfirmation = $this->secret('Ulangi password');

    $validator = Validator::make(
        compact('name', 'email', 'password', 'passwordConfirmation'),
        [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                'same:passwordConfirmation',
                Password::min(12)->letters()->mixedCase()->numbers()->symbols(),
            ],
        ],
        ['password.same' => 'Konfirmasi password tidak cocok.'],
    );

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::where('email', $email)->first();
    $wasCreated = $user === null;

    $user ??= new User;
    $user->fill(['name' => $name, 'email' => $email, 'password' => $password]);
    $user->save();

    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $role->syncPermissions(Permission::where('guard_name', 'web')->get());
    $user->syncRoles($role);

    if (! $wasCreated) {
        $user->tokens()->delete();
    }

    $this->newLine();
    $this->info($wasCreated ? 'Super admin berhasil dibuat.' : 'Super admin berhasil diperbarui.');
    $this->line('Email: '.$user->email);
    $this->warn('Password tidak ditampilkan atau disimpan di log. Simpan di password manager.');

    return 0;
})->purpose('Membuat atau memperbarui credential super admin secara aman');
