<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->vehicles() as $vehicle) {
                $ownerEmail = $vehicle['owner_email'];
                unset($vehicle['owner_email']);
                $owner = DB::table('users')->where('email', $ownerEmail)->where('role', 'cliente')->where('is_active', true)->first(['id']);
                if (! $owner) {
                    throw new RuntimeException('A carga exige os dois clientes demonstrativos ativos.');
                }
                $existing = DB::table('vehicles')->where('license_plate', $vehicle['license_plate'])->first(['user_id']);
                if ($existing && $existing->user_id !== $owner->id) {
                    throw new RuntimeException('Placa demonstrativa já vinculada a outro proprietário.');
                }
                if ($existing) {
                    continue;
                }
                DB::table('vehicles')->insert([
                    ...$vehicle,
                    'user_id' => $owner->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function vehicles(): array
    {
        return [
            ['owner_email' => 'cliente1@theseguro.test', 'license_plate' => 'ABC1D23', 'brand' => 'Marca Exemplo', 'model' => 'Modelo Alfa', 'manufacture_year' => 2020, 'model_year' => 2021, 'color' => 'Prata'],
            ['owner_email' => 'cliente2@theseguro.test', 'license_plate' => 'DEF5678', 'brand' => 'Marca Exemplo', 'model' => 'Modelo Beta', 'manufacture_year' => 2018, 'model_year' => null, 'color' => null],
        ];
    }
}
