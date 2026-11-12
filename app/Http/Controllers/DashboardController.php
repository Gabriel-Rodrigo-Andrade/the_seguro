<?php

namespace App\Http\Controllers;

use App\Enums\ProposalStatus;
use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Http\Requests\DashboardRequest;
use App\Models\InsuranceContract;
use App\Models\Proposal;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request): View
    {
        $filters = $request->validated();
        $user = $request->user();
        $reference = CarbonImmutable::now('America/Sao_Paulo');
        $proposals = $this->period(Proposal::visibleTo($user), $filters);
        $assistance = $this->period(ServiceRequest::visibleTo($user), $filters);
        $contracts = $this->period(InsuranceContract::visibleTo($user)->activeAt($reference), $filters, 'contracted_at');
        if ($user->role === UserRole::Broker) {
            $proposals->where('status', ProposalStatus::Submitted->value);
            $assistance->whereIn('status', [ServiceRequestStatus::Open->value, ServiceRequestStatus::InProgress->value]);
        }
        if (! empty($filters['proposal_status'])) {
            $proposals->where('status', $filters['proposal_status']);
        }
        if (! empty($filters['assistance_status'])) {
            $assistance->where('status', $filters['assistance_status']);
        }
        $expiring = (clone $contracts)->where('ends_on', '<=', $reference->addDays(30)->toDateString());
        $counts = [
            'proposals' => (clone $proposals)->count(),
            'contracts' => (clone $contracts)->count(),
            'expiring' => (clone $expiring)->count(),
            'assistance' => (clone $assistance)->count(),
            'open' => (clone $assistance)->where('status', 'open')->count(),
            'in_progress' => (clone $assistance)->where('status', 'in_progress')->count(),
        ];
        if ($user->role === UserRole::Administrator) {
            $counts['users'] = $this->period(User::query(), $filters)->count();
            $counts['vehicles'] = $this->period(Vehicle::query(), $filters)->count();
        }

        return view('dashboard', [
            'counts' => $counts, 'filters' => $filters, 'reference' => $reference,
            'proposals' => $proposals->with(['customer', 'vehicle', 'plan'])->latest('created_at')->latest('id')->limit(5)->get(),
            'assistance' => $assistance->with(['contract.proposal.customer', 'contract.proposal.vehicle', 'assignee'])->oldest('created_at')->oldest('id')->limit(5)->get(),
            'contracts' => $contracts->with(['proposal.customer', 'proposal.vehicle'])->orderBy('ends_on')->orderBy('id')->limit(5)->get(),
            'expiring' => $expiring->with(['proposal.customer', 'proposal.vehicle'])->orderBy('ends_on')->orderBy('id')->limit(5)->get(),
        ]);
    }

    private function period(Builder $query, array $filters, string $column = 'created_at'): Builder
    {
        if (! empty($filters['date_from'])) {
            $query->where($column, '>=', CarbonImmutable::parse($filters['date_from'], 'America/Sao_Paulo')->startOfDay()->utc());
        }
        if (! empty($filters['date_to'])) {
            $query->where($column, '<', CarbonImmutable::parse($filters['date_to'], 'America/Sao_Paulo')->startOfDay()->addDay()->utc());
        }

        return $query;
    }
}
