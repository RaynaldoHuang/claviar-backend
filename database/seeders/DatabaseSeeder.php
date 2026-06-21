<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $permissions = collect(['manage consignors', 'manage products', 'manage sales', 'manage payouts', 'view reports'])
            ->map(fn ($name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions($permissions);
        $admin->syncPermissions($permissions);
        if (! app()->environment('production')) {
            $user = User::firstOrCreate(['email' => 'admin@claviar.test'], ['name' => 'Claviar Admin', 'password' => 'password']);
            $user->syncRoles($superAdmin);
        }
        foreach (['Tas', 'Sepatu', 'Pakaian', 'Aksesori'] as $name) {
            Category::firstOrCreate(compact('name'));
        }
        foreach (['Chanel', 'Gucci', 'Louis Vuitton', 'Prada', 'Coach'] as $name) {
            Brand::firstOrCreate(compact('name'));
        }
    }
}
