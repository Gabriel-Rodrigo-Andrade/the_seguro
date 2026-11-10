<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Cadastrar veículo</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session("status"))<p role="status" class="text-green-800">{{ session("status") }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="POST" action="{{ route('vehicles.store') }}" class="max-w-xl space-y-5">@csrf @include('vehicles._form')</form>
</div></div></div>
</x-app-layout>
