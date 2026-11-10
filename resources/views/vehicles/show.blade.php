<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Detalhes do veículo</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session("status"))<p role="status" class="text-green-800">{{ session("status") }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<dl class="grid sm:grid-cols-2 gap-4">
@foreach(['license_plate'=>'Placa','brand'=>'Marca','model'=>'Modelo','manufacture_year'=>'Ano de fabricação','model_year'=>'Ano-modelo','color'=>'Cor'] as $field=>$label)<div><dt class="font-semibold">{{ $label }}</dt><dd>{{ $vehicle->$field ?? 'Não informado' }}</dd></div>@endforeach
<div><dt class="font-semibold">Proprietário</dt><dd>{{ $vehicle->owner->name }}</dd></div></dl>
@can('update', $vehicle)<a class="underline text-indigo-700" href="{{ route('vehicles.edit', $vehicle) }}">Editar veículo</a>@endcan
@can('delete', $vehicle)<form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}">@csrf @method('DELETE')<p class="mb-2">A exclusão remove o veículo cadastrado.</p><x-danger-button>Excluir veículo</x-danger-button></form>@endcan
<a class="underline" href="{{ route('vehicles.index') }}">Voltar à lista</a>
</div></div></div>
</x-app-layout>
