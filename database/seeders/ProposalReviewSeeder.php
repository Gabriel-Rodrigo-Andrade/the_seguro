<?php

namespace Database\Seeders;

use App\Actions\Proposals\ReviewProposal;
use App\Models\Plan;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProposalReviewSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $reviewer = User::where('email', 'corretor@theseguro.test')->where('role', 'corretor')->where('is_active', true)->first();
            if (! $reviewer) {
                throw new RuntimeException('A carga exige o corretor demonstrativo ativo.');
            }
            foreach ([['ABC1D23', 'cliente1@theseguro.test', 'approved', 'Demonstração E5: aprovação fictícia após conferência.'], ['DEF5678', 'cliente2@theseguro.test', 'rejected', 'Demonstração E5: recusa fictícia por documentação incompleta.']] as [$plate, $email, $decision, $notes]) {
                if (Proposal::where('review_notes', $notes)->exists()) {
                    continue;
                }
                $vehicle = Vehicle::where('license_plate', $plate)->whereHas('owner', fn ($owner) => $owner->where('email', $email)->where('role', 'cliente')->where('is_active', true))->first();
                $plan = Plan::where('name', 'Completo')->with('services')->first();
                if (! $vehicle || ! $plan || ! $plan->isAvailable()) {
                    throw new RuntimeException('A carga exige veículos dos clientes demonstrativos e o plano Completo disponível.');
                }
                $proposal = Proposal::create(['user_id' => $vehicle->user_id, 'vehicle_id' => $vehicle->id, 'plan_id' => $plan->id, 'quoted_amount' => $plan->annual_price, 'status' => 'submitted', 'submitted_at' => now(), 'services_snapshot' => $plan->services->map(fn ($service) => ['id' => $service->id, 'name' => $service->name, 'description' => $service->description])->all()]);
                app(ReviewProposal::class)->handle($reviewer, $proposal, $decision, $notes);
            }
        });
    }
}
