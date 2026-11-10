<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Painel</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session("status"))<p role="status" class="text-green-800">{{ session("status") }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h1 class="text-2xl font-semibold">Olá, {{ auth()->user()->name }}.</h1>
<p>Seu perfil: <strong>{{ auth()->user()->role->label() }}</strong>.</p>
<p>Bem-vindo ao TheSeguro. O cadastro de veículos e o catálogo de planos já estão disponíveis. Propostas e assistência serão disponibilizadas nas próximas versões.</p>
@can('viewAny', App\Models\User::class)<a class="underline text-indigo-700" href="{{ route('users.index') }}">Gerenciar usuários</a>@endcan
<p><a class="underline" href="{{ route('vehicles.index') }}">Consultar veículos</a></p>
<p><a class="underline" href="{{ route('plans.index') }}">Consultar planos</a></p>
<p><a class="underline" href="{{ route('profile.edit') }}">Editar meu perfil</a></p>
</div></div></div>
</x-app-layout>
