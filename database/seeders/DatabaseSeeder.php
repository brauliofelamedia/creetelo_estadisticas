<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);
        $this->call(ConfigSeeder::class);

        $braulio = User::create([
            'name' => 'Braulio',
            'last_name' => 'Miramontes',
            'email' => 'braulio@felamedia.com',
            'phone' => '1234567890',
            'bio' => 'I am a Super Admin',
            'password' => Hash::make('password')
        ]);
        
        $jorge = User::create([
            'name' => 'Jorge',
            'last_name' => 'Fela',
            'email' => 'jorge@felamedia.com',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $testing = User::create([
            'name' => 'Test',
            'last_name' => 'Test',
            'email' => 'test@test.com',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $adam = User::create([
            'name' => 'Adam',
            'last_name' => 'Stramwasser',
            'email' => 'adam@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('Creetelo@club123')
        ]);

        $michelle = User::create([
            'name' => 'Michelle',
            'last_name' => 'Poler',
            'email' => 'michelle@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('Creetelo@club123')
        ]);

        $braulio->assignRole('super_admin');
        $jorge->assignRole('admin');
        $michelle->assignRole('admin');
        $adam->assignRole('admin');
        $testing->assignRole('admin');
    }
}
