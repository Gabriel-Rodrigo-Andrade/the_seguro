<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Plano</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h3 class="font-semibold text-lg">{{ $plan->name }}</h3><p class="whitespace-pre-line">{{ $plan->description }}</p><p>{{ $plan->is_active ? 'Ativo' : 'Inativo' }}</p><p>{{ $plan->is_active && $plan->services->isNotEmpty() && $plan->services->every(fn ($service) => $service->is_active) ? 'Disponível no catálogo' : 'Indisponível: confira o estado do plano e dos serviços.' }}</p><p>Preço anual fictício: R$ {{ number_format((float)$plan->annual_price, 2, ',', '.') }}</p> <h3 class="font-semibold">Composição</h3><ul class="list-disc pl-5">@forelse($plan->services as $service)<li>{{ $service->name }} — {{ $service->description }} @if(!$service->is_active)<strong>(Inativo)</strong>@endif</li>@empty<li>Sem serviços.</li>@endforelse</ul>
@can('update', $plan)<a class="underline text-indigo-700" href="{{ route('plans.edit', $plan) }}">Editar</a>
<p>Desativar preserva o registro. Excluir remove definitivamente; vínculos podem impedir a exclusão.</p>
<form method="POST" action="{{ route('plans.destroy', $plan) }}">@csrf @method('DELETE')<x-danger-button>Excluir plano</x-danger-button></form>@endcan
<a class="underline" href="{{ route('plans.index') }}">Voltar</a>
</div></div></div></x-app-layout>
