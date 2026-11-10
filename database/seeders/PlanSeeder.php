<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->plans() as $plan) {
                if (DB::table('plans')->where('name', $plan['name'])->exists()) {
                    continue;
                }
                $serviceIds = DB::table('services')->whereIn('name', $plan['services'])->where('is_active', true)->pluck('id');
                if ($serviceIds->count() !== count($plan['services'])) {
                    throw new RuntimeException('A carga exige todos os serviços demonstrativos ativos.');
                }
                unset($plan['services']);
                $planId = DB::table('plans')->insertGetId([
                    ...$plan, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
                foreach ($serviceIds as $serviceId) {
                    DB::table('plan_service')->insert([
                        'plan_id' => $planId, 'service_id' => $serviceId,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        });
    }

    private function plans(): array
    {
        return [
            ['name' => 'Essencial', 'description' => 'Plano fictício com assistência de guincho.', 'annual_price' => '600.00', 'services' => ['Guincho']],
            ['name' => 'Completo', 'description' => 'Plano fictício com guincho e chaveiro.', 'annual_price' => '960.00', 'services' => ['Guincho', 'Chaveiro']],
        ];
    }
}
