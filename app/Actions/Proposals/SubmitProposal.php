<?php

namespace App\Actions\Proposals;

use App\Models\Plan;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitProposal
{
    public function confirmationToken(Proposal $proposal): string
    {
        return Crypt::encryptString(json_encode($this->quote($proposal, $proposal->plan), JSON_THROW_ON_ERROR));
    }

    private function quote(Proposal $proposal, Plan $plan): array
    {
        return ['proposal_id' => $proposal->id, 'user_id' => $proposal->user_id, 'vehicle_id' => $proposal->vehicle_id, 'plan_id' => $plan->id,
            'amount' => $plan->annual_price, 'composition_hash' => hash('sha256', json_encode($this->snapshot($plan), JSON_THROW_ON_ERROR))];
    }

    private function snapshot(Plan $plan): array
    {
        return $plan->services->sortBy('id')->map(fn ($service) => ['id' => $service->id, 'name' => $service->name, 'description' => $service->description])->values()->all();
    }

    public function handle(User $customer, Proposal $proposal, string $token): void
    {
        DB::transaction(function () use ($customer, $proposal, $token) {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($customer->fresh())->authorize('submit', $proposal);
            $vehicle = $proposal->vehicle()->lockForUpdate()->firstOrFail();
            if ($vehicle->user_id !== $customer->id) {
                throw ValidationException::withMessages(['vehicle_id' => 'O veículo não pertence ao cliente.']);
            }
            $plan = $proposal->plan()->lockForUpdate()->firstOrFail();
            $plan->setRelation('services', $plan->services()->orderBy('services.id')->lockForUpdate()->get());
            if (! $plan->is_active || $plan->services->isEmpty() || $plan->services->contains(fn ($service) => ! $service->is_active)) {
                throw ValidationException::withMessages(['plan_id' => 'Plano indisponível. Edite o rascunho e escolha outro plano.']);
            }
            try {
                $confirmed = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
            } catch (DecryptException|\JsonException) {
                throw ValidationException::withMessages(['quote_token' => 'Confirmação inválida. Reabra a proposta e confira a cotação.']);
            }
            $current = $this->quote($proposal, $plan);
            if ($confirmed !== $current) {
                throw ValidationException::withMessages(['quote_token' => 'A cotação mudou. Confira o preço e a composição atualizados e confirme novamente.']);
            }
            $proposal->update(['quoted_amount' => $plan->annual_price, 'services_snapshot' => $this->snapshot($plan), 'status' => 'submitted', 'submitted_at' => now()]);
        });
    }
}
