<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Serviços</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@can('create', App\Models\Service::class)<a class="underline text-indigo-700" href="{{ route('services.create') }}">Cadastrar serviço</a>@endcan
<div class="grid gap-4 md:grid-cols-2">@forelse($services as $service)<article class="border rounded-lg p-4 space-y-3"><h3 class="font-semibold">{{ $service->name }}</h3><p>{{ $service->description }}</p><p>{{ $service->is_active ? 'Ativo' : 'Inativo' }}</p>
<a class="underline text-indigo-700" href="{{ route('services.show', $service) }}">Ver detalhes de {{ $service->name }}</a></article>@empty<p>Nenhum registro disponível.</p>@endforelse</div>{{ $services->links() }}
</div></div></div></x-app-layout>
