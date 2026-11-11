<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Contratos</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h3 class="font-semibold text-lg">{{ $contract->contract_number }}</h3><p>Registro interno de contratação fictícia acadêmica. Não é uma apólice real.</p>
<p>Cliente: {{ $contract->proposal->customer->name }}</p><p>Veículo: {{ $contract->proposal->vehicle->license_plate }} — {{ $contract->proposal->vehicle->brand }} {{ $contract->proposal->vehicle->model }}</p><p>Plano: {{ $contract->proposal->plan->name }}</p>
<p>Preço anual contratado: <strong>R$ {{ number_format((float)$contract->proposal->quoted_amount, 2, ',', '.') }}</strong></p>
<p>Confirmação: {{ $contract->contracted_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
<p>Vigência: {{ $contract->starts_on->format('d/m/Y') }} até {{ $contract->ends_on->subDay()->format('d/m/Y') }}, inclusive. Fim exclusivo: {{ $contract->ends_on->format('d/m/Y') }}, às 00:00 (horário de São Paulo).</p><p>{{ $contract->isActiveAt() ? 'Vigente' : 'Fora da vigência' }}</p>
<h4 class="font-semibold">Serviços contratados</h4><ul class="list-disc pl-5">@foreach($contract->proposal->services_snapshot ?? [] as $service)<li>{{ $service['name'] }} — {{ $service['description'] }}</li>@endforeach</ul>
<a class="underline" href="{{ route('proposals.show', $contract->proposal) }}">Ver proposta</a> <a class="underline" href="{{ route('contracts.index') }}">Voltar</a>
</div></div></div></x-app-layout>
