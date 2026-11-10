<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Proposal;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            foreach ([['ABC1D23', 'cliente1@theseguro.test', 'Essencial', 'draft'], ['DEF5678', 'cliente2@theseguro.test', 'Completo', 'submitted']] as [$plate, $email, $planName, $status]) {
                $vehicle = Vehicle::where('license_plate', $plate)->whereHas('owner', fn ($owner) => $owner->where('email', $email)->where('role', 'cliente')->where('is_active', true))->first();
                $plan = Plan::where('name', $planName)->with('services')->first();
                if (! $vehicle || ! $plan) {
                    throw new RuntimeException('A carga exige veículos e planos demonstrativos existentes e clientes ativos.');
                }
                if (Proposal::where('vehicle_id', $vehicle->id)->where('plan_id', $plan->id)->where('user_id', $vehicle->user_id)->exists()) {
                    continue;
                }
                if (! $plan->isAvailable()) {
                    throw new RuntimeException('A carga exige planos demonstrativos disponíveis.');
                }
                $snapshot = null;
                $submittedAt = null;
                if ($status === 'submitted') {
                    $snapshot = $plan->services->sortBy('id')->map(fn ($service) => ['id' => $service->id, 'name' => $service->name, 'description' => $service->description])->values()->all();
                    $submittedAt = now();
                }
                Proposal::create(['user_id' => $vehicle->user_id, 'vehicle_id' => $vehicle->id, 'plan_id' => $plan->id, 'quoted_amount' => $plan->annual_price, 'status' => $status, 'services_snapshot' => $snapshot, 'submitted_at' => $submittedAt]);
            }
        });
    }
}
