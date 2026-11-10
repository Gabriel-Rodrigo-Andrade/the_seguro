<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Propostas</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h3 class="font-semibold">{{ $proposal->exists ? 'Editar rascunho' : 'Nova cotação' }}</h3>
<p>Escolha seu veículo e um plano. O preço será calculado pelo sistema. Salvar um rascunho não envia a proposta.</p>
<form method="POST" action="{{ $proposal->exists ? route('proposals.update', $proposal) : route('proposals.store') }}" class="max-w-xl space-y-5">@csrf @if($proposal->exists) @method('PUT') @endif
<div><x-input-label for="vehicle_id" value="Seu veículo"/><select id="vehicle_id" name="vehicle_id" class="w-full rounded-md border-gray-300" required><option value="">Selecione</option>@foreach($vehicles as $vehicle)<option value="{{ $vehicle->id }}" @selected((string)old('vehicle_id', $proposal->vehicle_id) === (string)$vehicle->id)>{{ $vehicle->license_plate }} — {{ $vehicle->brand }} {{ $vehicle->model }}</option>@endforeach</select></div>
<div><x-input-label for="plan_id" value="Plano disponível"/><select id="plan_id" name="plan_id" class="w-full rounded-md border-gray-300" required><option value="">Selecione</option>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected((string)old('plan_id', $proposal->plan_id) === (string)$plan->id)>{{ $plan->name }} — R$ {{ number_format((float)$plan->annual_price, 2, ',', '.') }} / ano</option>@endforeach</select></div>
@if($vehicles->isEmpty())<p>Cadastre um veículo antes de cotar.</p>@endif
@if($plans->isEmpty())<p>Não há planos disponíveis no momento.</p>@endif
<x-primary-button>Salvar rascunho e conferir cotação</x-primary-button></form>
<h3 class="font-semibold">Serviços dos planos disponíveis</h3>@foreach($plans as $plan)<p><strong>{{ $plan->name }}:</strong> {{ $plan->services->pluck('name')->join(', ') }}</p>@endforeach
<a class="underline" href="{{ route('proposals.index') }}">Voltar</a>
</div></div></div></x-app-layout>
