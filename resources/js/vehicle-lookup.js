const normalizePlate = (value) => value.trim().replace(/[ -]/g, '').toUpperCase();

export function initializeVehicleLookup(section) {
    const form = section.closest('form');
    const plate = form.querySelector('[name="license_plate"]');
    const button = section.querySelector('[data-lookup-button]');
    const status = section.querySelector('[data-lookup-status]');
    const results = section.querySelector('[data-lookup-results]');
    const summary = section.querySelector('[data-lookup-summary]');
    const apply = section.querySelector('[data-apply-button]');
    let revision = 0;
    let cached = null;
    let pending = false;
    plate.addEventListener('input', () => {
        revision += 1;
        cached = null;
        results.hidden = true;
        status.textContent = 'Placa alterada. Consulte novamente se desejar.';
    });
    button.addEventListener('click', async () => {
        if (pending) return;
        const requestedPlate = normalizePlate(plate.value);
        if (!/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(requestedPlate)) {
            status.textContent = 'Informe uma placa brasileira válida.';
            return;
        }
        if (cached?.plate === requestedPlate) {
            results.hidden = false;
            status.textContent = 'Sugestões já consultadas nesta página. Nenhuma nova consulta realizada.';
            return;
        }
        const requestedRevision = revision;
        pending = true;
        button.disabled = true;
        results.hidden = true;
        status.textContent = 'Consultando…';
        try {
            const response = await fetch(section.dataset.url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value },
                body: JSON.stringify({ license_plate: requestedPlate }),
            });
            const payload = await response.json();
            if (requestedRevision !== revision || normalizePlate(plate.value) !== requestedPlate) return;
            if (!response.ok) {
                status.textContent = payload.message || 'Consulta indisponível. Preencha manualmente.';
                return;
            }
            cached = { plate: requestedPlate, data: payload.data };
            status.textContent = payload.source === 'mock' ? 'Simulação: dados fictícios, sem consulta externa.' : 'Sugestões recebidas da FipeAPI.';
            const year = payload.data.suggested_year ?? 'não informado';
            summary.textContent = `Marca: ${payload.data.brand ?? 'não informada'}
Modelo: ${payload.data.model ?? 'não informado'}
Cor: ${payload.data.color ?? 'não informada'}
Ano informado pela consulta: ${year}. Confirme os anos manualmente.
${payload.warnings.join(' ')}`;
            if (payload.quota) summary.textContent += `
Cota restante: ${payload.quota.remaining ?? 'desconhecida'} / ${payload.quota.limit ?? 'desconhecida'}.`;
            results.hidden = false;
        } catch {
            if (requestedRevision === revision) status.textContent = 'Não foi possível consultar. Preencha manualmente.';
        } finally {
            pending = false;
            button.disabled = false;
        }
    });
    apply.addEventListener('click', () => {
        if (!cached || cached.plate !== normalizePlate(plate.value)) return;
        const fields = ['brand', 'model', 'color'].filter((name) => cached.data[name]);
        const replaces = fields.some((name) => form.elements[name].value.trim() && form.elements[name].value !== cached.data[name]);
        if (replaces && !window.confirm('Substituir os campos preenchidos pelas sugestões exibidas?')) return;
        fields.forEach((name) => { form.elements[name].value = cached.data[name]; });
        status.textContent = 'Sugestões aplicadas. Revise os dados, confirme os anos e use Salvar veículo.';
    });
}

document.querySelectorAll('[data-vehicle-lookup]').forEach(initializeVehicleLookup);
