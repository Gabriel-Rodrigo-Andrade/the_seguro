@if(!isset($vehicle) && auth()->user()->role === App\Enums\UserRole::Administrator)
<div><x-input-label for="user_id" value="Cliente proprietário"/><select class="w-full rounded-md border-gray-300" name="user_id" id="user_id" required><option value="">Selecione um cliente ativo</option>@foreach($owners as $owner)<option value="{{ $owner->id }}" @selected((string)old('user_id') === (string)$owner->id)>{{ $owner->name }} — {{ $owner->email }}</option>@endforeach</select></div>
@endif
<div><x-input-label for="license_plate" value="Placa"/><x-text-input id="license_plate" name="license_plate" class="block w-full" :value="old('license_plate', $vehicle->license_plate ?? '')" required maxlength="10" placeholder="ABC1D23 ou ABC-1234"/></div>
@if(!isset($vehicle))
<section class="rounded-md border border-indigo-200 p-4 space-y-3" data-vehicle-lookup data-url="{{ route('vehicles.lookup') }}">
<p class="font-semibold">{{ config('services.fipe.mode') === 'mock' ? 'Simulação — dados fictícios' : 'Consulta de placa' }}</p>
<p>A consulta não salva o veículo. O preenchimento manual está sempre disponível.</p>
<button type="button" data-lookup-button class="rounded-md bg-indigo-700 text-white px-4 py-2 disabled:opacity-50">Consultar placa</button>
<p data-lookup-status role="status" aria-live="polite"></p>
<div data-lookup-results hidden class="space-y-3"><p data-lookup-summary class="whitespace-pre-line"></p><button type="button" data-apply-button class="rounded-md border border-indigo-700 text-indigo-700 px-4 py-2">Aplicar sugestões revisadas</button></div>
<noscript>JavaScript desativado. Preencha os dados manualmente.</noscript>
</section>
@endif
@foreach(['brand'=>'Marca','model'=>'Modelo','color'=>'Cor (opcional)'] as $field=>$label)
<div><x-input-label :for="$field" :value="$label"/><x-text-input :id="$field" :name="$field" class="block w-full" :value="old($field, isset($vehicle) ? $vehicle->$field : '')" :required="$field !== 'color'" :maxlength="$field === 'color' ? 50 : 100"/></div>
@endforeach
@foreach(['manufacture_year'=>'Ano de fabricação (confirme)','model_year'=>'Ano-modelo (opcional)'] as $field=>$label)
<div><x-input-label :for="$field" :value="$label"/><x-text-input :id="$field" :name="$field" type="number" min="1886" :max="now('America/Sao_Paulo')->year + 1" class="block w-full" :value="old($field, isset($vehicle) ? $vehicle->$field : '')" :required="$field === 'manufacture_year'"/></div>
@endforeach
<x-primary-button>Salvar veículo</x-primary-button><a class="underline ml-4" href="{{ route('vehicles.index') }}">Cancelar</a>
