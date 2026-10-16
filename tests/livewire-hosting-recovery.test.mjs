import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../public/js/livewire-hosting-recovery.js', import.meta.url), 'utf8');

function setup() {
    let interceptor;
    let reloads = 0;
    const notices = [];
    const listeners = {};
    const button = { addEventListener(name, callback) { this[name] = callback; }, focus() {} };
    const copyButton = { addEventListener() {} };
    const document = {
        addEventListener(name, callback) { listeners[name] = callback; },
        createElement() { return { style: {}, setAttribute() {}, querySelector(selector) { return selector === 'button' ? button : copyButton; } }; },
        body: { appendChild(notice) { notices.push(notice); } },
    };
    const window = {
        Livewire: { interceptRequest(callback) { interceptor = callback; } },
        location: { reload() { reloads++; } },
    };
    vm.runInNewContext(source, { window, document });
    return {
        notices, button, window, document,
        get reloads() { return reloads; },
        request(body) {
            let cancelled = false;
            let prevented = false;
            let handler;
            interceptor({ request: { cancel() { cancelled = true; } }, onError(callback) { handler = callback; } });
            handler?.({ body, preventDefault() { prevented = true; } });
            return { cancelled, prevented };
        },
    };
}

test('hosting challenge is suppressed, later requests stop, and no reload is automatic', () => {
    const app = setup();
    const result = app.request('<html><script src="/aes.js"></script></html>');
    assert.equal(result.prevented, true);
    assert.equal(app.notices.length, 1);
    assert.equal(app.reloads, 0);
    assert.equal(app.request('').cancelled, true);
    assert.equal(app.notices.length, 1);
    app.button.click();
    assert.equal(app.reloads, 1);
});

test('inline browser challenge is recognized', () => {
    const app = setup();
    assert.equal(app.request('<html><script>function toNumbers() {} function toHex() {}</script></html>').prevented, true);
});

test('ordinary JSON and Laravel error pages keep their normal handling', () => {
    const app = setup();
    for (const body of ['{"components":[]}', '<html>Server Error</html>', '<html><script>console.log("error")</script></html>']) {
        assert.equal(app.request(body).prevented, false);
    }
    assert.equal(app.notices.length, 0);
});
