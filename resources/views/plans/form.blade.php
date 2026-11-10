<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Cadastrar / editar plano</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $plan->exists ? route('plans.update', $plan) : route('plans.store') }}" class="max-w-xl space-y-5">@csrf @if($plan->exists) @method('PUT') @endif
<div><x-input-label for="name" value="Nome"/><x-text-input id="name" name="name" class="w-full" :value="old('name', $plan->name)" maxlength="100" required/></div>
<div><x-input-label for="description" value="Descrição"/><textarea id="description" name="description" class="w-full rounded-md border-gray-300" rows="4" maxlength="10000" required>{{ old('description', $plan->description) }}</textarea></div>
<input type="hidden" name="is_active" value="0"><label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan->is_active))>Ativo</label>
<div><x-input-label for="annual_price" value="Preço anual fictício (R$)"/><x-text-input id="annual_price" name="annual_price" type="number" min="0.01" max="9999999999.99" step="0.01" :value="old('annual_price', $plan->annual_price)" required/></div>
<fieldset class="space-y-3"><legend class="font-semibold">Serviços (selecione ao menos um ativo)</legend>
@forelse($services as $service)<label class="flex items-center gap-3"><input type="checkbox" name="services[]" value="{{ $service->id }}" @checked(in_array((string)$service->id, array_map('strval', array_filter((array)old('services', $plan->services->modelKeys()), 'is_scalar')))) @disabled(!$service->is_active)><span>{{ $service->name }} @if(!$service->is_active)(Inativo — não pode integrar a composição salva)@endif</span></label>@empty<p>Cadastre um serviço ativo antes de salvar um plano.</p>@endforelse
</fieldset>
<x-primary-button>Salvar plano</x-primary-button> <a class="underline" href="{{ route('plans.index') }}">Cancelar</a></form>
</div></div></div></x-app-layout>
