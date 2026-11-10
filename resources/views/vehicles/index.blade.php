<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Veículos</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session("status"))<p role="status" class="text-green-800">{{ session("status") }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@can('create', App\Models\Vehicle::class)<a class="underline text-indigo-700" href="{{ route('vehicles.create') }}">Cadastrar veículo</a>@endcan
<form method="GET" action="{{ route('vehicles.index') }}" class="flex flex-wrap gap-3 items-end"><div><x-input-label for="search" value="Buscar por placa, marca ou modelo"/><x-text-input id="search" name="search" :value="$search" maxlength="100"/></div><x-primary-button>Buscar</x-primary-button><a class="underline" href="{{ route('vehicles.index') }}">Limpar</a></form>
<div class="overflow-x-auto"><table class="min-w-full text-left"><caption class="sr-only">Veículos disponíveis para consulta</caption><thead><tr>@foreach(['Placa','Marca / modelo','Proprietário','Ações'] as $heading)<th class="p-3" scope="col">{{ $heading }}</th>@endforeach</tr></thead><tbody>
@forelse($vehicles as $vehicle)<tr class="border-t"><td class="p-3">{{ $vehicle->license_plate }}</td><td class="p-3">{{ $vehicle->brand }} / {{ $vehicle->model }}</td><td class="p-3">{{ $vehicle->owner->name }}</td><td class="p-3"><a class="underline text-indigo-700" href="{{ route('vehicles.show', $vehicle) }}" aria-label="Ver veículo {{ $vehicle->license_plate }}">Ver detalhes</a></td></tr>
@empty<tr><td colspan="4" class="p-3">Nenhum veículo disponível.</td></tr>@endforelse
</tbody></table></div>{{ $vehicles->links() }}
</div></div></div>
</x-app-layout>
