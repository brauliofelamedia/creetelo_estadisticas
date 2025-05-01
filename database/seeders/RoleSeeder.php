<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles y asignar permisos
        $superAdmin = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web'
        ]);
        $admin = Role::create([
            'name' => 'admin',
            'guard_name' => 'web'
        ]);
        $user = Role::create([
            'name' => 'user',
            'guard_name' => 'web'
        ]);
    }
}
