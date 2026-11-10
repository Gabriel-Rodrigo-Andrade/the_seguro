<?php

namespace Database\Seeders;

use App\Models\InsuranceContract;
use App\Models\Plan;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class InsuranceContractSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $reviewer = User::where('email', 'corretor@theseguro.test')->where('role', 'corretor')->where('is_active', true)->first();
            $plan = Plan::where('name', 'Completo')->with('services')->first();
            if (! $reviewer || ! $plan || ! $plan->isAvailable()) {
                throw new RuntimeException('A carga exige corretor demonstrativo ativo e plano Completo disponível.');
            }
            foreach ([['ABC1D23', 'cliente1@theseguro.test', 1, 'Demonstração E6: contrato vigente.'], ['DEF5678', 'cliente2@theseguro.test', 13, 'Demonstração E6: contrato encerrado.']] as [$plate, $email, $monthsAgo, $notes]) {
                if (Proposal::where('review_notes', $notes)->exists()) {
                    continue;
                }
                $vehicle = Vehicle::where('license_plate', $plate)->whereHas('owner', fn ($owner) => $owner->where('email', $email)->where('role', 'cliente')->where('is_active', true))->first();
                if (! $vehicle) {
                    throw new RuntimeException('A carga exige os veículos e clientes demonstrativos.');
                }
                $start = CarbonImmutable::today('America/Sao_Paulo')->subMonthsNoOverflow($monthsAgo);
                $instant = $start->setTime(12, 0)->utc();
                $proposal = Proposal::create(['user_id' => $vehicle->user_id, 'vehicle_id' => $vehicle->id, 'plan_id' => $plan->id, 'quoted_amount' => $plan->annual_price, 'status' => 'approved', 'services_snapshot' => $plan->services->map(fn ($service) => ['id' => $service->id, 'name' => $service->name, 'description' => $service->description])->all(), 'submitted_at' => $instant, 'reviewed_by' => $reviewer->id, 'reviewed_at' => $instant, 'review_notes' => $notes, 'created_at' => $instant, 'updated_at' => $instant]);
                InsuranceContract::create(['proposal_id' => $proposal->id, 'contract_number' => 'TS-'.Str::ulid(), 'contracted_at' => $instant, 'starts_on' => $start->toDateString(), 'ends_on' => $start->addMonthsNoOverflow(12)->toDateString(), 'created_at' => $instant, 'updated_at' => $instant]);
                $proposal->update(['status' => 'contracted', 'updated_at' => $instant]);
            }
        });
    }
}
