<?php

namespace App\Http\Controllers;

use App\Actions\Contracts\ConfirmProposal;
use App\Models\InsuranceContract;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InsuranceContractController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', InsuranceContract::class);

        return view('contracts.index', ['contracts' => InsuranceContract::visibleTo($request->user())->with(['proposal.customer', 'proposal.vehicle', 'proposal.plan'])->latest('id')->paginate(15)]);
    }

    public function show(InsuranceContract $contract): View
    {
        Gate::authorize('view', $contract);

        return view('contracts.show', ['contract' => $contract->load(['proposal.customer', 'proposal.vehicle', 'proposal.plan'])]);
    }

    public function confirm(Request $request, Proposal $proposal, ConfirmProposal $confirm): RedirectResponse
    {
        return to_route('contracts.show', $confirm->handle($request->user(), $proposal))->with('status', 'Contratação fictícia confirmada.');
    }
}
