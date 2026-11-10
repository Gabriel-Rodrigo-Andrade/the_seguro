<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\SavePlanRequest;
use App\Models\Plan;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Plan::class);
        $query = Plan::with('services')->orderBy('name');
        if ($request->user()->role === UserRole::Customer) {
            $query->available();
        }

        return view('plans.index', ['plans' => $query->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Plan::class);

        return $this->form(new Plan(['is_active' => true]));
    }

    public function edit(Plan $plan): View
    {
        Gate::authorize('update', $plan);

        return $this->form($plan);
    }

    private function form(Plan $plan): View
    {
        return view('plans.form', ['plan' => $plan->load('services'), 'services' => Service::orderBy('name')->get()]);
    }

    public function show(Plan $plan): View
    {
        Gate::authorize('view', $plan);

        return view('plans.show', ['plan' => $plan->load('services')]);
    }

    public function store(SavePlanRequest $request): RedirectResponse
    {
        $plan = $this->save(new Plan, $request->validated());

        return to_route('plans.show', $plan)->with('status', 'Plano cadastrado.');
    }

    public function update(SavePlanRequest $request, Plan $plan): RedirectResponse
    {
        $plan = $this->save($plan, $request->validated());

        return to_route('plans.show', $plan)->with('status', 'Plano atualizado.');
    }

    private function save(Plan $plan, array $attributes): Plan
    {
        return DB::transaction(function () use ($plan, $attributes) {
            if ($plan->exists) {
                $plan = Plan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            }
            $serviceIds = $attributes['services'];
            unset($attributes['services']);
            $services = Service::whereIn('id', $serviceIds)->orderBy('id')->lockForUpdate()->get();
            if ($services->count() !== count($serviceIds) || $services->contains(fn (Service $service) => ! $service->is_active)) {
                throw ValidationException::withMessages(['services' => 'Selecione somente serviços disponíveis.']);
            }
            $plan->fill($attributes)->save();
            $plan->services()->sync($serviceIds);

            return $plan;
        });
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        Gate::authorize('delete', $plan);
        DB::transaction(function () use ($plan) {
            $plan = Plan::whereKey($plan->id)->lockForUpdate()->firstOrFail();
            $plan->services()->detach();
            $plan->delete();
        });

        return to_route('plans.index')->with('status', 'Plano excluído.');
    }
}
