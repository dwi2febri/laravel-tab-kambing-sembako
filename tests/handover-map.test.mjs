import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../resources/js/handover-map.js', import.meta.url), 'utf8').replace('export { loadMaps };', '');
const flush = () => new Promise(resolve => setImmediate(resolve));
function node(value = '') {
    return { value, hidden: false, disabled: false, textContent: '', events: {}, attributes: {},
        classList: { add() {}, remove() {} },
        addEventListener(name, callback) { this.events[name] = callback; },
        setAttribute(name, value) { this.attributes[name] = value; },
        replaceChildren() {}, scrollIntoView() {}, remove() {},
    };
}
function setup({ networkFailure = false } = {}) {
    const nodes = Object.fromEntries(['[name="preferred_at"]', '[name="latitude"]', '[name="longitude"]', '[name="location"]', '[data-location-status]', '[data-handover-map]', '[data-map-panel]', '[data-map-loading]', '[data-map-loading-text]', '[data-map-retry]', '[data-device-location]', '[data-date-day]', '[data-open-map]'].map(key => [key, node()]));
    const form = node();
    form.dataset = { mapsKey: 'test-key' };
    form.querySelector = selector => nodes[selector];
    let constructed = 0, geolocate, loadedTiles;
    const libraries = [];
    const scripts = [];
    const maps = {
        importLibrary(name) {
            libraries.push(name);
            if (name === 'maps') return Promise.resolve({ Map: class {
                constructor() { constructed++; }
                addListener() {}
                setCenter() {}
                setZoom() {}
            } });
            return new Promise(() => {});
        },
        event: { addListenerOnce(_, name, callback) { if (name === 'tilesloaded') loadedTiles = callback; }, clearInstanceListeners() {} },
    };
    vm.runInNewContext(source, {
        window: networkFailure ? {} : { google: { maps } },
        navigator: { geolocation: { getCurrentPosition(callback) { geolocate = callback; } } },
        document: { querySelectorAll: () => [form], createElement: () => node(), head: { append(script) { scripts.push(script); queueMicrotask(() => script.onerror()); } } },
        URLSearchParams, setTimeout: () => 1, clearTimeout() {}, Intl, Date, Promise,
    });
    return { nodes, form, libraries, scripts, count: () => constructed, tiles: () => loadedTiles(), locate: point => geolocate({ coords: point }) };
}

test('preloads the map before a click and renders without waiting for marker or geocoder', async () => {
    const page = setup();
    await flush();
    assert.ok(page.libraries.includes('maps'));
    assert.ok(page.libraries.includes('marker'));
    page.nodes['[data-open-map]'].events.click();
    page.nodes['[data-open-map]'].events.click();
    await flush();
    assert.equal(page.count(), 1);
    assert.equal(page.libraries.includes('geocoding'), false);
    page.tiles();
    assert.equal(page.nodes['[data-map-loading]'].hidden, true);
    assert.equal(page.nodes['[data-map-panel]'].attributes['aria-busy'], 'false');
});

test('device coordinates are available immediately even while address lookup is pending', async () => {
    const page = setup();
    page.nodes['[data-device-location]'].events.click();
    page.locate({ latitude: -6.9667, longitude: 110.4167 });
    assert.equal(page.nodes['[name="latitude"]'].value, '-6.9667000');
    assert.equal(page.nodes['[name="longitude"]'].value, '110.4167000');
    assert.equal(page.nodes['[data-device-location]'].disabled, false);
    await flush();
    assert.equal(page.count(), 1);
    assert.ok(page.libraries.includes('geocoding'));
});

test('failed loading can retry instead of reusing a permanently rejected promise', async () => {
    const page = setup({ networkFailure: true });
    await flush();
    page.nodes['[data-open-map]'].events.click();
    await flush();
    assert.equal(page.nodes['[data-map-retry]'].hidden, false);
    const before = page.scripts.length;
    page.nodes['[data-map-retry]'].events.click();
    await flush();
    assert.equal(page.scripts.length, before + 1);
});
