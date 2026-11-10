<?php

namespace App\Actions\Contracts;

use App\Enums\ProposalStatus;
use App\Models\InsuranceContract;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ConfirmProposal
{
    public function handle(User $customer, Proposal $proposal): InsuranceContract
    {
        return DB::transaction(function () use ($customer, $proposal) {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($customer->fresh())->authorize('confirm', $proposal);
            if ($proposal->status === ProposalStatus::Contracted) {
                return $proposal->contract()->firstOrFail();
            }
            $instant = now();
            $start = $instant->copy()->timezone('America/Sao_Paulo')->startOfDay();
            $contract = InsuranceContract::create([
                'proposal_id' => $proposal->id,
                'contract_number' => 'TS-'.Str::ulid(),
                'contracted_at' => $instant,
                'starts_on' => $start->toDateString(),
                'ends_on' => $start->copy()->addMonthsNoOverflow(12)->toDateString(),
            ]);
            $proposal->update(['status' => ProposalStatus::Contracted]);

            return $contract;
        });
    }
}
