import test from 'node:test';
import assert from 'node:assert/strict';

globalThis.document = { querySelectorAll: () => [] };
const { initializeVehicleLookup } = await import('../../resources/js/vehicle-lookup.js');

function element(value = '') {
    return { value, hidden: true, disabled: false, textContent: '', listeners: {}, addEventListener(name, listener) { this.listeners[name] = listener; } };
}
function setup() {
    const fields = Object.fromEntries(['license_plate', 'brand', 'model', 'color', 'manufacture_year', 'model_year'].map(name => [name, element()]));
    fields.license_plate.value = 'XYZ1A23';
    const csrf = element('csrf-test');
    const form = { elements: fields, querySelector: selector => selector === '[name="_token"]' ? csrf : fields.license_plate };
    const controls = Object.fromEntries(['button', 'status', 'results', 'summary', 'apply'].map(name => [name, element()]));
    const selectors = { '[data-lookup-button]': controls.button, '[data-lookup-status]': controls.status, '[data-lookup-results]': controls.results, '[data-lookup-summary]': controls.summary, '[data-apply-button]': controls.apply };
    initializeVehicleLookup({ dataset: { url: '/vehicles/lookup' }, closest: () => form, querySelector: selector => selectors[selector] });
    globalThis.window = { confirm: () => true };
    return { fields, controls, click: () => controls.button.listeners.click(), apply: () => controls.apply.listeners.click(), change: () => fields.license_plate.listeners.input() };
}
const success = (changes = {}) => ({ ok: true, json: async () => ({ source: 'mock', data: { license_plate: 'XYZ1A23', brand: 'Marca simulada', model: 'Modelo simulado', color: 'Prata', suggested_year: 2020, ...changes }, warnings: ['Simulação'] }) });

test('consultar não altera campos; aplicar preserva anos e reutiliza resultado sem nova chamada', async () => {
    const ui = setup(); let calls = 0;
    ui.fields.manufacture_year.value = '2019'; ui.fields.model_year.value = '2021';
    globalThis.fetch = async (url, options) => { calls++; assert.equal(url, '/vehicles/lookup'); assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf-test'); return success(); };
    await ui.click();
    assert.equal(ui.fields.brand.value, '');
    assert.match(ui.controls.status.textContent, /Simulação/);
    ui.apply();
    assert.equal(ui.fields.brand.value, 'Marca simulada');
    assert.equal(ui.fields.manufacture_year.value, '2019');
    assert.equal(ui.fields.model_year.value, '2021');
    await ui.click(); assert.equal(calls, 1);
});

test('resposta atrasada é descartada após alteração da placa e clique duplicado não envia', async () => {
    const ui = setup(); let resolve; let calls = 0;
    globalThis.fetch = () => { calls++; return new Promise(done => { resolve = done; }); };
    const pending = ui.click();
    assert.equal(ui.controls.button.disabled, true);
    await ui.click(); assert.equal(calls, 1);
    ui.fields.license_plate.value = 'ABC1234'; ui.change();
    resolve(success()); await pending;
    assert.equal(ui.controls.results.hidden, true);
    assert.equal(ui.controls.button.disabled, false);
    ui.apply(); assert.equal(ui.fields.brand.value, '');
});

test('resposta parcial não apaga campo e substituição exige confirmação', async () => {
    const ui = setup(); ui.fields.brand.value = 'Minha marca'; ui.fields.color.value = 'Azul';
    globalThis.fetch = async () => success({ color: null });
    await ui.click();
    globalThis.window.confirm = () => false;
    ui.apply(); assert.equal(ui.fields.brand.value, 'Minha marca');
    globalThis.window.confirm = () => true;
    ui.apply(); assert.equal(ui.fields.brand.value, 'Marca simulada'); assert.equal(ui.fields.color.value, 'Azul');
});

test('falha mantém formulário e reabilita consulta, sem retry', async () => {
    const ui = setup(); ui.fields.brand.value = 'Manual'; let calls = 0;
    globalThis.fetch = async () => { calls++; throw new Error('offline'); };
    await ui.click();
    assert.equal(calls, 1); assert.equal(ui.fields.brand.value, 'Manual');
    assert.equal(ui.controls.button.disabled, false); assert.match(ui.controls.status.textContent, /manualmente/);
});

test('placa inválida não inicia requisição', async () => {
    const ui = setup(); ui.fields.license_plate.value = 'invalid';
    globalThis.fetch = async () => assert.fail('Não deve acessar a rede');
    await ui.click(); assert.match(ui.controls.status.textContent, /válida/);
});
