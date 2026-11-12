<?php

namespace App\Http\Controllers;

use App\Actions\Assistance\UpdateServiceRequestStatus;
use App\Http\Requests\StoreAssistanceRequest;
use App\Http\Requests\UpdateAssistanceStatusRequest;
use App\Models\InsuranceContract;
use App\Models\ServiceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ServiceRequest::class);

        return view('assistance.index', ['requests' => ServiceRequest::visibleTo($request->user())->with(['contract.proposal.customer', 'contract.proposal.vehicle', 'assignee'])->orderByRaw("CASE WHEN status = 'completed' THEN 1 ELSE 0 END")->orderBy('created_at')->orderBy('id')->paginate(15)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', ServiceRequest::class);
        $today = now('America/Sao_Paulo')->toDateString();

        return view('assistance.create', ['contracts' => InsuranceContract::visibleTo($request->user())->where('starts_on', '<=', $today)->where('ends_on', '>', $today)->with('proposal.vehicle')->get()]);
    }

    public function store(StoreAssistanceRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $assistance = DB::transaction(function () use ($request, $attributes) {
            $contract = InsuranceContract::whereKey($attributes['insurance_contract_id'])->whereHas('proposal', fn ($proposal) => $proposal->where('user_id', $request->user()->id))->lockForUpdate()->first();
            Gate::forUser($request->user()->fresh())->authorize('create', ServiceRequest::class);
            if (! $contract || ! $contract->isActiveAt()) {
                throw ValidationException::withMessages(['insurance_contract_id' => 'Selecione um contrato seu que esteja vigente.']);
            }
            $services = collect($contract->proposal->services_snapshot)->pluck('id')->map(fn ($id) => (int) $id);
            if (! $services->contains((int) $attributes['service_id'])) {
                throw ValidationException::withMessages(['service_id' => 'O serviço não faz parte desta contratação.']);
            }

            return ServiceRequest::create([...$attributes, 'status' => 'open', 'assigned_to' => null, 'response' => null, 'completed_at' => null]);
        });

        return to_route('assistance.show', $assistance)->with('status', 'Solicitação fictícia registrada.');
    }

    public function show(ServiceRequest $serviceRequest): View
    {
        Gate::authorize('view', $serviceRequest);

        return view('assistance.show', ['assistance' => $serviceRequest->load(['contract.proposal.customer', 'contract.proposal.vehicle', 'assignee'])]);
    }

    public function update(UpdateAssistanceStatusRequest $request, ServiceRequest $serviceRequest, UpdateServiceRequestStatus $update): RedirectResponse
    {
        $update->handle($request->user(), $serviceRequest, $request->validated('status'), $request->validated('response'));

        return to_route('assistance.show', $serviceRequest)->with('status', 'Atendimento atualizado.');
    }
}
