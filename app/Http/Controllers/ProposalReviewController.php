<?php

namespace App\Http\Controllers;

use App\Actions\Proposals\ReviewProposal;
use App\Http\Requests\ReviewProposalRequest;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProposalReviewController extends Controller
{
    public function index(): View
    {
        Gate::authorize('reviewAny', Proposal::class);

        return view('proposal-reviews.index', ['proposals' => Proposal::where('status', 'submitted')->with(['customer', 'vehicle', 'plan'])->orderBy('submitted_at')->orderBy('id')->paginate(15)]);
    }

    public function update(ReviewProposalRequest $request, Proposal $proposal, ReviewProposal $review): RedirectResponse
    {
        $review->handle($request->user(), $proposal, $request->validated('decision'), $request->validated('review_notes'));

        return to_route('proposals.show', $proposal)->with('status', 'Análise registrada. O cliente já pode consultar o resultado.');
    }
}
