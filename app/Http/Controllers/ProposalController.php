<?php

namespace App\Http\Controllers;

use App\Actions\Proposals\SaveDraftProposal;
use App\Actions\Proposals\SubmitProposal;
use App\Http\Requests\StoreProposalRequest;
use App\Http\Requests\SubmitProposalRequest;
use App\Http\Requests\UpdateProposalRequest;
use App\Models\Plan;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Proposal::class);

        return view('proposals.index', ['proposals' => Proposal::visibleTo($request->user())->with(['customer', 'vehicle', 'plan'])->latest('id')->paginate(15)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Proposal::class);

        return $this->form($request, new Proposal);
    }

    public function edit(Request $request, Proposal $proposal): View
    {
        Gate::authorize('update', $proposal);

        return $this->form($request, $proposal);
    }

    private function form(Request $request, Proposal $proposal): View
    {
        return view('proposals.form', ['proposal' => $proposal, 'vehicles' => $request->user()->vehicles()->orderBy('license_plate')->get(), 'plans' => Plan::available()->with('services')->orderBy('name')->get()]);
    }

    public function store(StoreProposalRequest $request, SaveDraftProposal $save): RedirectResponse
    {
        return to_route('proposals.show', $save->handle($request->user(), $request->validated()))->with('status', 'Cotação salva como rascunho. Confira antes de enviar.');
    }

    public function update(UpdateProposalRequest $request, Proposal $proposal, SaveDraftProposal $save): RedirectResponse
    {
        return to_route('proposals.show', $save->handle($request->user(), $request->validated(), $proposal))->with('status', 'Rascunho atualizado.');
    }

    public function show(Proposal $proposal, SubmitProposal $submit): View
    {
        Gate::authorize('view', $proposal);
        $proposal->load(['customer', 'vehicle', 'plan.services']);

        return view('proposals.show', ['proposal' => $proposal, 'quoteToken' => $submit->confirmationToken($proposal)]);
    }

    public function submit(SubmitProposalRequest $request, Proposal $proposal, SubmitProposal $submit): RedirectResponse
    {
        $submit->handle($request->user(), $proposal, $request->validated('quote_token'));

        return to_route('proposals.show', $proposal)->with('status', 'Proposta enviada. Preço e composição preservados.');
    }

    public function destroy(Request $request, Proposal $proposal): RedirectResponse
    {
        DB::transaction(function () use ($request, $proposal) {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $proposal = Proposal::whereKey($proposal->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($request->user()->fresh())->authorize('delete', $proposal);
            $proposal->delete();
        });

        return to_route('proposals.index')->with('status', 'Rascunho excluído.');
    }
}
