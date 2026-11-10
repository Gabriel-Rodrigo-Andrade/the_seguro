<?php

namespace App\Actions\Proposals;

use App\Models\Plan;
use App\Models\Proposal;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaveDraftProposal
{
    public function handle(User $customer, array $attributes, ?Proposal $proposal = null): Proposal
    {
        return DB::transaction(function () use ($customer, $attributes, $proposal) {
            // Ordem comum antes de locks de linha nas mutações de propostas/catálogo/veículos.
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $customer = $customer->fresh();
            if ($proposal) {
                $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($customer)->authorize('update', $proposal);
            }
            if (! $proposal) {
                Gate::forUser($customer)->authorize('create', Proposal::class);
            }
            $vehicle = Vehicle::whereKey($attributes['vehicle_id'])->where('user_id', $customer->id)->lockForUpdate()->first();
            if (! $vehicle) {
                throw ValidationException::withMessages(['vehicle_id' => 'Selecione um veículo seu.']);
            }
            $plan = Plan::whereKey($attributes['plan_id'])->lockForUpdate()->first();
            if (! $plan || ! $plan->isAvailable()) {
                throw ValidationException::withMessages(['plan_id' => 'O plano não está disponível.']);
            }
            $proposal ??= new Proposal;
            $proposal->fill(['user_id' => $customer->id, 'vehicle_id' => $vehicle->id, 'plan_id' => $plan->id, 'quoted_amount' => $plan->annual_price, 'status' => 'draft', 'services_snapshot' => null, 'submitted_at' => null])->save();

            return $proposal;
        });
    }
}
