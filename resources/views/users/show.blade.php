<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Detalhes do usuário</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session("status"))<p role="status" class="text-green-800">{{ session("status") }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<dl class="space-y-3"><div><dt class="font-semibold">Nome</dt><dd>{{ $user->name }}</dd></div><div><dt class="font-semibold">E-mail</dt><dd>{{ $user->email }}</dd></div><div><dt class="font-semibold">Perfil</dt><dd>{{ $user->role->label() }}</dd></div><div><dt class="font-semibold">Situação</dt><dd>{{ $user->is_active ? 'Ativo' : 'Inativo' }}</dd></div></dl>
@can('update', $user)<a class="underline text-indigo-700" href="{{ route('users.edit', $user) }}">Editar usuário</a>@endcan
@if($user->is_active) @can('deactivate', $user)<form method="POST" action="{{ route('users.deactivate', $user) }}">@csrf @method('PATCH')<p class="mb-2">A desativação impede o acesso, preservando o histórico da conta.</p><x-danger-button>Desativar usuário</x-danger-button></form>@endcan @endif
<a class="underline" href="{{ route('users.index') }}">Voltar à lista</a>
</div></div></div>
</x-app-layout>
