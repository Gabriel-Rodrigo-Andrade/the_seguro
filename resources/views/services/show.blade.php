<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Serviço</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h3 class="font-semibold text-lg">{{ $service->name }}</h3><p class="whitespace-pre-line">{{ $service->description }}</p><p>{{ $service->is_active ? 'Ativo' : 'Inativo' }}</p> 
@can('update', $service)<a class="underline text-indigo-700" href="{{ route('services.edit', $service) }}">Editar</a>
<p>Desativar preserva o registro. Excluir remove definitivamente; vínculos podem impedir a exclusão.</p>
<form method="POST" action="{{ route('services.destroy', $service) }}">@csrf @method('DELETE')<x-danger-button>Excluir serviço</x-danger-button></form>@endcan
<a class="underline" href="{{ route('services.index') }}">Voltar</a>
</div></div></div></x-app-layout>
