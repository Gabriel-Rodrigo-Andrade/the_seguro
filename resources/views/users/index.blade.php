<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Usuários</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session("status"))<p role="status" class="text-green-800">{{ session("status") }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<a class="underline text-indigo-700" href="{{ route('users.create') }}">Cadastrar usuário</a>
<div class="overflow-x-auto"><table class="min-w-full text-left"><caption class="sr-only">Contas cadastradas</caption>
<thead><tr>@foreach(['Nome', 'E-mail', 'Perfil', 'Situação', 'Ações'] as $heading)<th scope="col" class="p-3">{{ $heading }}</th>@endforeach</tr></thead>
<tbody>@forelse($users as $user)<tr class="border-t">
<td class="p-3">{{ $user->name }}</td><td class="p-3">{{ $user->email }}</td><td class="p-3">{{ $user->role->label() }}</td><td class="p-3">{{ $user->is_active ? 'Ativo' : 'Inativo' }}</td>
<td class="p-3"><a class="underline text-indigo-700" href="{{ route('users.show', $user) }}" aria-label="Ver {{ $user->name }}">Ver detalhes</a></td>
</tr>@empty<tr><td colspan="5" class="p-3">Nenhum usuário cadastrado.</td></tr>@endforelse</tbody></table></div>
{{ $users->links() }}
</div></div></div>
</x-app-layout>
