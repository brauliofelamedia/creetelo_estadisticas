<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Config;

class ConfigSeeder extends Seeder
{
    public function run(): void
    {
        // Crear roles y asignar permisos
        Config::create([
            'site_name' => 'Fela Media',
            'tags' => 'wowfriday_plan mensual,wowfriday_plan anual,creetelo_mensual,créetelo_mensual,creetelo_anual,créetelo_anual'
        ]);
    }
}
