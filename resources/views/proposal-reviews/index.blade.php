<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Fila de análise</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p>Propostas enviadas, ordenadas da mais antiga para a mais recente. Abra os detalhes para analisar.</p>
<div class="overflow-x-auto"><table class="min-w-full text-left"><caption class="sr-only">Propostas disponíveis</caption><thead><tr>@foreach(['Número', 'Cliente', 'Placa', 'Plano', 'Valor salvo', 'Situação', 'Ações'] as $heading)<th class="p-3" scope="col">{{ $heading }}</th>@endforeach</tr></thead><tbody>
@forelse($proposals as $proposal)<tr class="border-t"><td class="p-3">{{ $proposal->id }}</td><td class="p-3">{{ $proposal->customer->name }}</td><td class="p-3">{{ $proposal->vehicle->license_plate }}</td><td class="p-3">{{ $proposal->plan->name }}</td><td class="p-3">R$ {{ number_format((float)$proposal->quoted_amount, 2, ',', '.') }}</td><td class="p-3">{{ $proposal->status->label() }}</td><td class="p-3"><a class="underline" href="{{ route('proposals.show', $proposal) }}">Ver proposta {{ $proposal->id }}</a></td></tr>@empty<tr><td colspan="7" class="p-3">Nenhuma proposta disponível.</td></tr>@endforelse
</tbody></table></div>{{ $proposals->links() }}
</div></div></div></x-app-layout>
