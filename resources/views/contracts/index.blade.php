<x-app-layout>
<x-slot name="header"><h2 class="font-semibold text-xl text-gray-800">Contratos</h2></x-slot>
<div class="py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="bg-white p-6 shadow sm:rounded-lg space-y-6">
@if(session('status'))<p role="status" class="text-green-800">{{ session('status') }}</p>@endif
@if($errors->any())<div role="alert" class="text-red-700"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<p>Contratos fictícios para demonstração acadêmica.</p><div class="overflow-x-auto"><table class="min-w-full text-left"><thead><tr><th class="p-3">Número</th><th class="p-3">Cliente / veículo</th><th class="p-3">Vigência</th><th class="p-3">Situação</th></tr></thead><tbody>
@forelse($contracts as $contract)<tr class="border-t"><td class="p-3"><a class="underline" href="{{ route('contracts.show', $contract) }}">{{ $contract->contract_number }}</a></td><td class="p-3">{{ $contract->proposal->customer->name }} / {{ $contract->proposal->vehicle->license_plate }}</td><td class="p-3">{{ $contract->starts_on->format('d/m/Y') }} até {{ $contract->ends_on->subDay()->format('d/m/Y') }} (inclusive)</td><td class="p-3">{{ $contract->isActiveAt() ? 'Vigente' : 'Fora da vigência' }}</td></tr>@empty<tr><td colspan="4" class="p-3">Nenhum contrato disponível.</td></tr>@endforelse
</tbody></table></div>{{ $contracts->links() }}
</div></div></div></x-app-layout>
