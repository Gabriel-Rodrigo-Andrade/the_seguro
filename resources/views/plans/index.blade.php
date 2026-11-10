<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Planos</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@can('create', App\Models\Plan::class)<a class="underline text-indigo-700" href="{{ route('plans.create') }}">Cadastrar plano</a>@endcan
<div class="grid gap-4 md:grid-cols-2">@forelse($plans as $plan)<article class="border rounded-lg p-4 space-y-3"><h3 class="font-semibold">{{ $plan->name }}</h3><p>{{ $plan->description }}</p><p>{{ $plan->is_active ? 'Ativo' : 'Inativo' }}</p><p>{{ $plan->is_active && $plan->services->isNotEmpty() && $plan->services->every(fn ($service) => $service->is_active) ? 'Disponível no catálogo' : 'Indisponível: confira o estado do plano e dos serviços.' }}</p><p>Preço anual fictício: R$ {{ number_format((float)$plan->annual_price, 2, ',', '.') }}</p>
<a class="underline text-indigo-700" href="{{ route('plans.show', $plan) }}">Ver detalhes de {{ $plan->name }}</a></article>@empty<p>Nenhum registro disponível.</p>@endforelse</div>{{ $plans->links() }}
</div></div></div></x-app-layout>
