<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parametry;

class ParametrySeeder extends Seeder
{
    public function run(): void
    {
        Parametry::create([
            'name' => 'Registro',
            'state' => 1,
        ]);
    }
}
