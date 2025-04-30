<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Helper function to create a user only if they don't exist
     */
    private function createUserIfNotExists(array $userData)
    {
        $user = User::where('email', $userData['email'])->first();
        if (!$user) {
            return User::create($userData);
        }
        return $user;
    }

    public function run(): void
    {
        $braulio = $this->createUserIfNotExists([
            'name' => 'Braulio',
            'last_name' => 'Miramontes',
            'email' => 'braulio@felamedia.com',
            'phone' => '1234567890',
            'bio' => 'I am a Super Admin',
            'password' => Hash::make('password')
        ]);
        
        $jorge = $this->createUserIfNotExists([
            'name' => 'Jorge',
            'last_name' => 'Fela',
            'email' => 'jorge@felamedia.com',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $adam = $this->createUserIfNotExists([
            'name' => 'Adam',
            'last_name' => 'Stramwasser',
            'email' => 'adam@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('Creetelo@club123')
        ]);

        $michelle = $this->createUserIfNotExists([
            'name' => 'Michelle',
            'last_name' => 'Poler',
            'email' => 'michelle@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('Creetelo@club123')
        ]);

        $juan = $this->createUserIfNotExists([
            'name' => 'Juan',
            'last_name' => 'Cruz Encina',
            'email' => 'juan@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $sophi = $this->createUserIfNotExists([
            'name' => 'Sophi',
            'last_name' => 'Jacobs',
            'email' => 'sophi@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $michelle_gui = $this->createUserIfNotExists([
            'name' => 'Michelle',
            'last_name' => 'Guiralt',
            'email' => 'michi@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $rosana = $this->createUserIfNotExists([
            'name' => 'Rosana',
            'last_name' => 'Finol',
            'email' => 'rosy@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $liza = $this->createUserIfNotExists([
            'name' => 'Liza',
            'last_name' => 'Lopez',
            'email' => 'liza@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $jonathan = $this->createUserIfNotExists([
            'name' => 'Jonathan',
            'last_name' => 'Benaim',
            'email' => 'jonathan@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $debbie = $this->createUserIfNotExists([
            'name' => 'Debbie',
            'last_name' => 'Pappe',
            'email' => 'deborah@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $karla = $this->createUserIfNotExists([
            'name' => 'Karla',
            'last_name' => 'Belisario',
            'email' => 'karla@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $aura = $this->createUserIfNotExists([
            'name' => 'Aura',
            'last_name' => 'Isabel',
            'email' => 'auraisabel@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $jorelys = $this->createUserIfNotExists([
            'name' => 'Jorelys',
            'last_name' => 'quiaro',
            'email' => 'jorelys@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $paula = $this->createUserIfNotExists([
            'name' => 'Paula',
            'last_name' => 'Diaz',
            'email' => 'pau@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $vanessa = $this->createUserIfNotExists([
            'name' => 'Vanessa',
            'last_name' => 'Rozo F',
            'email' => 'vanessa@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $mena = $this->createUserIfNotExists([
            'name' => 'Mena',
            'last_name' => 'cardenas',
            'email' => 'mena@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $paulina = $this->createUserIfNotExists([
            'name' => 'Paulina',
            'last_name' => 'Cortés',
            'email' => 'paulina@creetelo.club',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $testing = $this->createUserIfNotExists([
            'name' => 'Test',
            'last_name' => 'Test',
            'email' => 'test@test.com',
            'phone' => '1234567890',
            'bio' => 'I am a Admin',
            'password' => Hash::make('password')
        ]);

        $braulio->assignRole('super_admin');
        $jorge->assignRole('admin');
        $michelle->assignRole('admin');
        $adam->assignRole('admin');
        $juan->assignRole('admin');
        $sophi->assignRole('admin');
        $michelle_gui->assignRole('admin');
        $rosana->assignRole('admin');
        $liza->assignRole('admin');
        $jonathan->assignRole('admin');
        $debbie->assignRole('admin');
        $karla->assignRole('admin');
        $aura->assignRole('admin');
        $jorelys->assignRole('admin');
        $paula->assignRole('admin');
        $vanessa->assignRole('admin');
        $mena->assignRole('admin');
        $paulina->assignRole('admin');
        $testing->assignRole('admin');
    }
}
