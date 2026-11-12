<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Assistência</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<h3 class="font-semibold">Solicitação #{{ $assistance->id }} — {{ $assistance->status->label() }}</h3><p>Atendimento fictício, sem despacho real de assistência.</p>
<p>Cliente: {{ $assistance->contract->proposal->customer->name }} — Placa: {{ $assistance->contract->proposal->vehicle->license_plate }}</p><p>Serviço contratado: {{ $assistance->contractedService()['name'] ?? '' }} — {{ $assistance->contractedService()['description'] ?? '' }}</p>
<p>Aberta em {{ $assistance->created_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p><p class="whitespace-pre-line">Relato: {{ $assistance->description }}</p><p>Local: {{ $assistance->location }}</p><p>Responsável: {{ $assistance->assignee?->name ?? 'Aguardando' }}</p>
@if($assistance->completed_at)<p>Concluída em {{ $assistance->completed_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i') }}</p><p class="whitespace-pre-line">Resposta: {{ $assistance->response }}</p>@endif
@can('take', $assistance)<form method="POST" action="{{ route('assistance.status', $assistance) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="in_progress"><x-primary-button>Assumir atendimento</x-primary-button></form>@endcan
@can('complete', $assistance)<form method="POST" action="{{ route('assistance.status', $assistance) }}" class="max-w-xl space-y-3">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><x-input-label for="response" value="Resposta do atendimento"/><textarea id="response" name="response" rows="4" maxlength="10000" required class="w-full rounded-md border-gray-300">{{ old('response') }}</textarea><x-primary-button>Concluir atendimento</x-primary-button></form>@endcan
<a class="underline" href="{{ route('assistance.index') }}">Voltar</a>
</div></div></div></x-app-layout>