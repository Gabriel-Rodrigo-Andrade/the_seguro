<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveServiceRequest;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Service::class);

        return view('services.index', ['services' => Service::orderBy('name')->paginate(15)]);
    }

    public function create(): View
    {
        Gate::authorize('create', Service::class);

        return view('services.form', ['service' => new Service(['is_active' => true])]);
    }

    public function store(SaveServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());

        return to_route('services.show', $service)->with('status', 'Serviço cadastrado.');
    }

    public function show(Service $service): View
    {
        Gate::authorize('view', $service);

        return view('services.show', compact('service'));
    }

    public function edit(Service $service): View
    {
        Gate::authorize('update', $service);

        return view('services.form', compact('service'));
    }

    public function update(SaveServiceRequest $request, Service $service): RedirectResponse
    {
        DB::transaction(function () use ($request, $service) {
            Service::whereKey($service->id)->lockForUpdate()->firstOrFail()->update($request->validated());
        });

        return to_route('services.show', $service)->with('status', 'Serviço atualizado.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        Gate::authorize('delete', $service);
        DB::transaction(function () use ($service) {
            $service = Service::whereKey($service->id)->lockForUpdate()->firstOrFail();
            if ($service->plans()->exists()) {
                throw ValidationException::withMessages(['service' => 'Serviço associado a plano não pode ser excluído. Você pode desativá-lo.']);
            }
            $service->delete();
        });

        return to_route('services.index')->with('status', 'Serviço excluído.');
    }
}
