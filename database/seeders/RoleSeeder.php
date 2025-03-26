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
            'uuid' => Str::uuid(),
            'name' => 'super_admin'
        ]);
        $admin = Role::create([
            'uuid' => Str::uuid(),
            'name' => 'admin'
        ]);
        $user = Role::create([
            'uuid' => Str::uuid(),
            'name' => 'user'
        ]);
    }
}
