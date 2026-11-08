<div><x-input-label for="name" value="Nome"/><x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $user->name ?? '')" required maxlength="255" autocomplete="name"/></div>
<div><x-input-label for="email" value="E-mail"/><x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email ?? '')" required maxlength="255" autocomplete="email"/></div>
<div><x-input-label for="role" value="Perfil"/><select id="role" name="role" class="rounded-md border-gray-300 w-full" required>@foreach($roles as $role)<option value="{{ $role->value }}" @selected(old('role', $user->role->value ?? 'cliente') === $role->value)>{{ $role->label() }}</option>@endforeach</select></div>
<div><x-input-label for="is_active" value="Situação"/><select id="is_active" name="is_active" class="rounded-md border-gray-300 w-full"><option value="1" @selected((string) old('is_active', isset($user) ? (int) $user->is_active : 1) === '1')>Ativo</option><option value="0" @selected((string) old('is_active', isset($user) ? (int) $user->is_active : 1) === '0')>Inativo</option></select></div>
@if(!isset($user))
<div><x-input-label for="password" value="Senha"/><x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required/></div>
<div><x-input-label for="password_confirmation" value="Confirmar senha"/><x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required/></div>
@endif
<x-primary-button>Salvar usuário</x-primary-button>
<a class="underline ml-4" href="{{ route('users.index') }}">Cancelar</a>
