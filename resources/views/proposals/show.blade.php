<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Propostas</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h3 class="font-semibold text-lg">Proposta #{{ $proposal->id }} — {{ $proposal->status->label() }}</h3>
<p>Cliente: {{ $proposal->customer->name }}</p><p>Veículo: {{ $proposal->vehicle->license_plate }} — {{ $proposal->vehicle->brand }} {{ $proposal->vehicle->model }}</p><p>Plano: {{ $proposal->plan->name }}</p>
@if($proposal->status === App\Enums\ProposalStatus::Draft)
<p>Preço anual atual: <strong>R$ {{ number_format((float)$proposal->plan->annual_price, 2, ',', '.') }}</strong></p><p>Esta é uma cotação fictícia. O preço e a composição são confirmados no envio.</p>
<ul class="list-disc pl-5">@foreach($proposal->plan->services as $service)<li>{{ $service->name }} — {{ $service->description }} @if(!$service->is_active)(Indisponível)@endif</li>@endforeach</ul>
@can('submit', $proposal)
@if($proposal->plan->isAvailable())<form method="POST" action="{{ route('proposals.submit', $proposal) }}">@csrf<input type="hidden" name="quote_token" value="{{ $quoteToken }}"><x-primary-button>Confirmar cotação e enviar proposta</x-primary-button></form>@else<p role="alert">Plano indisponível. Edite o rascunho e escolha outro plano.</p>@endif
@endcan
@else
<p>Preço anual enviado: <strong>R$ {{ number_format((float)$proposal->quoted_amount, 2, ',', '.') }}</strong></p><p>Enviada em {{ $proposal->submitted_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p>
<h4 class="font-semibold">Composição preservada no envio</h4><ul class="list-disc pl-5">@foreach($proposal->services_snapshot ?? [] as $service)<li>{{ $service['name'] }} — {{ $service['description'] }}</li>@endforeach</ul><p>A proposta enviada não pode mais ser editada ou excluída.</p>
@endif
@can('update', $proposal)<a class="underline" href="{{ route('proposals.edit', $proposal) }}">Editar rascunho</a>@endcan
@can('delete', $proposal)<form method="POST" action="{{ route('proposals.destroy', $proposal) }}">@csrf @method('DELETE')<x-danger-button>Excluir rascunho</x-danger-button></form>@endcan
<a class="underline" href="{{ route('proposals.index') }}">Voltar</a>
</div></div></div></x-app-layout>
