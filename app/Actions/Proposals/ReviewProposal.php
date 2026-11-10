<?php

namespace App\Actions\Proposals;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewProposal
{
    public function handle(User $reviewer, Proposal $proposal, string $decision, ?string $notes): void
    {
        DB::transaction(function () use ($reviewer, $proposal, $decision, $notes) {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($reviewer->fresh())->authorize('review', $proposal);
            if (! in_array($decision, [ProposalStatus::Approved->value, ProposalStatus::Rejected->value], true)) {
                throw ValidationException::withMessages(['decision' => 'Escolha aprovar ou recusar.']);
            }
            $notes = trim($notes ?? '');
            if ($decision === ProposalStatus::Rejected->value && $notes === '') {
                throw ValidationException::withMessages(['review_notes' => 'Informe a justificativa da recusa.']);
            }
            $proposal->update(['status' => $decision, 'reviewed_by' => $reviewer->id, 'reviewed_at' => now(), 'review_notes' => $notes !== '' ? $notes : null]);
        });
    }
}
