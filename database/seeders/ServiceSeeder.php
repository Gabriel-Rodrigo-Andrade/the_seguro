<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['Guincho' => 'Remoção fictícia do veículo para demonstração.', 'Chaveiro' => 'Assistência fictícia para abertura do veículo.'] as $name => $description) {
                if (DB::table('services')->where('name', $name)->exists()) {
                    continue;
                }
                DB::table('services')->insert([
                    'name' => $name, 'description' => $description, 'is_active' => true,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });
    }
}
