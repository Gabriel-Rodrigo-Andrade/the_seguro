<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Vehicle::class);
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? '';
        $query = Vehicle::visibleTo($request->user())->with('owner');
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('license_plate', 'ilike', '%'.Vehicle::normalizePlate($search).'%')
                    ->orWhere('brand', 'ilike', '%'.$search.'%')->orWhere('model', 'ilike', '%'.$search.'%');
            });
        }

        return view('vehicles.index', ['vehicles' => $query->latest('id')->paginate(15)->withQueryString(), 'search' => $search]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Vehicle::class);
        $owners = collect();
        if ($request->user()->role === UserRole::Administrator) {
            $owners = User::where('role', UserRole::Customer)->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email']);
        }

        return view('vehicles.create', compact('owners'));
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $ownerId = $request->user()->role === UserRole::Administrator ? $attributes['user_id'] : $request->user()->id;
        unset($attributes['user_id']);
        $vehicle = DB::transaction(function () use ($ownerId, $attributes) {
            $owner = User::whereKey($ownerId)->lockForUpdate()->firstOrFail();
            if (! $owner->is_active || $owner->role !== UserRole::Customer) {
                throw ValidationException::withMessages(['user_id' => 'Selecione um cliente ativo.']);
            }

            return $owner->vehicles()->create($attributes);
        });

        return to_route('vehicles.show', $vehicle)->with('status', 'Veículo cadastrado.');
    }

    public function show(Vehicle $vehicle): View
    {
        Gate::authorize('view', $vehicle);

        return view('vehicles.show', ['vehicle' => $vehicle->load('owner')]);
    }

    public function edit(Vehicle $vehicle): View
    {
        Gate::authorize('update', $vehicle);

        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        DB::transaction(function () use ($request, $vehicle) {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $vehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($request->user()->fresh())->authorize('update', $vehicle);
            $vehicle->fill($request->validated());
            if ($vehicle->isDirty() && $vehicle->proposals()->where('status', '!=', 'draft')->exists()) {
                throw ValidationException::withMessages(['vehicle' => 'Veículo com proposta enviada não pode ser alterado.']);
            }
            $vehicle->save();
        });

        return to_route('vehicles.show', $vehicle)->with('status', 'Veículo atualizado.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        Gate::authorize('delete', $vehicle);
        DB::transaction(function () use ($vehicle) {
            DB::statement('LOCK TABLE proposals IN SHARE ROW EXCLUSIVE MODE');
            $vehicle = Vehicle::whereKey($vehicle->id)->lockForUpdate()->firstOrFail();
            if ($vehicle->proposals()->exists()) {
                throw ValidationException::withMessages(['vehicle' => 'Veículo com proposta não pode ser excluído.']);
            }
            $vehicle->delete();
        });

        return to_route('vehicles.index')->with('status', 'Veículo excluído.');
    }
}
