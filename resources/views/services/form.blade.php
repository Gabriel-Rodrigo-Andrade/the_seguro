<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Cadastrar / editar serviço</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ $service->exists ? route('services.update', $service) : route('services.store') }}" class="max-w-xl space-y-5">@csrf @if($service->exists) @method('PUT') @endif
<div><x-input-label for="name" value="Nome"/><x-text-input id="name" name="name" class="w-full" :value="old('name', $service->name)" maxlength="100" required/></div>
<div><x-input-label for="description" value="Descrição"/><textarea id="description" name="description" class="w-full rounded-md border-gray-300" rows="4" maxlength="10000" required>{{ old('description', $service->description) }}</textarea></div>
<input type="hidden" name="is_active" value="0"><label class="flex items-center gap-3"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))>Ativo</label>

<x-primary-button>Salvar serviço</x-primary-button> <a class="underline" href="{{ route('services.index') }}">Cancelar</a></form>
</div></div></div></x-app-layout>
