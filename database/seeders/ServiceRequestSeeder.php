<?php

namespace Database\Seeders;

use App\Models\InsuranceContract;
use App\Models\ServiceRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServiceRequestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $operator = User::where('email', 'corretor@theseguro.test')->where('role', 'corretor')->where('is_active', true)->first();
            if (! $operator) {
                throw new RuntimeException('A carga exige o corretor demonstrativo ativo.');
            }
            foreach ([['open', 'Demonstração E6: contrato vigente.', 'Demonstração E7: solicitação aberta.'], ['in_progress', 'Demonstração E6: contrato vigente.', 'Demonstração E7: atendimento em andamento.'], ['completed', 'Demonstração E6: contrato encerrado.', 'Demonstração E7: atendimento concluído.']] as [$status, $contractNotes, $description]) {
                if (ServiceRequest::where('description', $description)->exists()) {
                    continue;
                }
                $contract = InsuranceContract::whereHas('proposal', fn ($proposal) => $proposal->where('review_notes', $contractNotes))->with('proposal')->first();
                if (! $contract || empty($contract->proposal->services_snapshot)) {
                    throw new RuntimeException('A carga exige os contratos demonstrativos e seus serviços.');
                }
                $opened = CarbonImmutable::parse($contract->starts_on->toDateString(), 'America/Sao_Paulo')->addDays(2)->setTime(10, 0)->utc();
                if (! $contract->isActiveAt($opened)) {
                    throw new RuntimeException('A data demonstrativa deve estar dentro da vigência.');
                }
                ServiceRequest::create(['insurance_contract_id' => $contract->id, 'service_id' => $contract->proposal->services_snapshot[0]['id'], 'description' => $description, 'location' => 'Local fictício de demonstração', 'status' => $status, 'assigned_to' => $status === 'open' ? null : $operator->id, 'response' => $status === 'completed' ? 'Atendimento fictício concluído com sucesso.' : null, 'completed_at' => $status === 'completed' ? $opened->addHour() : null, 'created_at' => $opened, 'updated_at' => $status === 'open' ? $opened : $opened->addHour()]);
            }
        });
    }
}
