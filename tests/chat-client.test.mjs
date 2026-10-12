import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function chat() {
    const source = readFileSync(new URL('../resources/js/app.js', import.meta.url), 'utf8');
    const start = source.indexOf("Alpine.data('adminChat'");
    const end = source.indexOf('Alpine.start();', start);
    let factory;
    vm.runInNewContext(source.slice(start, end), { Alpine: { data: (_, value) => { factory = value; } } });
    const instance = factory({ listUrl: '/chat/messages', sendUrl: '/chat/messages' });
    instance.$refs = { messages: { scrollHeight: 100, scrollTop: 0, clientHeight: 100 } };
    instance.$nextTick = callback => callback();
    return instance;
}

test('send works during polling and stale response cannot erase sent message', async () => {
    const client = chat();
    client.body = 'Pesan baru';
    let finishPoll;
    let posts = 0;
    client.request = async (_, options = {}) => {
        if (options.method === 'POST') {
            posts++;
            return { message: { id: 2, body: 'Pesan baru' } };
        }
        return new Promise(resolve => { finishPoll = resolve; });
    };
    const polling = client.load();
    assert.equal(client.loading, true);
    await client.send();
    assert.equal(posts, 1);
    assert.equal(client.sending, false);
    assert.equal(client.messages[0].body, 'Pesan baru');
    finishPoll({ reset: true, conversation_id: null, messages: [] });
    await polling;
    assert.equal(client.messages.length, 1);
    assert.equal(client.refreshNeeded, true);
});

test('failed send retains draft and releases send button', async () => {
    const client = chat();
    client.body = 'Coba lagi';
    client.request = async () => { throw new Error('Jaringan terputus'); };
    await client.send();
    assert.equal(client.body, 'Coba lagi');
    assert.equal(client.sending, false);
    assert.equal(client.error, 'Jaringan terputus');
});

test('send button does not depend on background loading', () => {
    const template = readFileSync(new URL('../resources/views/components/admin-chat.blade.php', import.meta.url), 'utf8');
    assert.ok(template.includes(':disabled="sending || !body.trim()"'));
    assert.ok(!template.includes('sending || loading'));
});
