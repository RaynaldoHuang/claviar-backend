<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_super_admin_with_a_secure_password(): void
    {
        Permission::create(['name' => 'view reports', 'guard_name' => 'web']);

        $this->artisan('user:create-super-admin', [
            '--name' => 'Production Admin',
            '--email' => 'owner@example.com',
        ])
            ->expectsQuestion('Password (minimal 12 karakter, huruf besar/kecil, angka, dan simbol)', 'Prod!Secure12345')
            ->expectsQuestion('Ulangi password', 'Prod!Secure12345')
            ->assertSuccessful();

        $user = User::where('email', 'owner@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('Prod!Secure12345', $user->password));
        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertTrue($user->can('view reports'));
    }

    public function test_it_rejects_a_weak_password(): void
    {
        $this->artisan('user:create-super-admin', [
            '--name' => 'Production Admin',
            '--email' => 'owner@example.com',
        ])
            ->expectsQuestion('Password (minimal 12 karakter, huruf besar/kecil, angka, dan simbol)', 'password')
            ->expectsQuestion('Ulangi password', 'password')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.com']);
    }
}
