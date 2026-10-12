@props(['inApp' => false])
<div class="admin-chat {{ $inApp ? 'admin-chat-in-app' : '' }}" x-data="adminChat({ listUrl: @js(route('chat.index')), sendUrl: @js(route('chat.store')) })" @keydown.escape.window="close()">
    <section class="chat-panel" x-cloak x-show="open" x-transition.opacity role="dialog" aria-label="Chat dengan admin SAKINA">
        <header class="chat-header">
            <span class="chat-admin-avatar"><i data-lucide="messages-square"></i></span>
            <div><strong>Admin SAKINA</strong><small>Pesan langsung ke pengelola</small></div>
            <button type="button" @click="close()" aria-label="Tutup chat" class="chat-close">&times;</button>
        </header>
        <div class="chat-identity" x-show="name" x-cloak>Chat sebagai <strong x-text="name"></strong></div>
        <div class="chat-messages" x-ref="messages" aria-live="polite" aria-relevant="additions">
            <button type="button" class="chat-older" x-show="hasMore" @click="loadOlder()" :disabled="olderLoading" x-text="olderLoading ? 'Memuat...' : 'Lihat pesan sebelumnya'"></button>
            <div class="chat-welcome" x-show="messages.length === 0"><strong>Ada yang bisa kami bantu?</strong><p>Tanyakan produk, tabungan, atau pembayaran. Admin akan membalas di sini.</p></div>
            <template x-for="message in messages" :key="message.id">
                <div class="chat-message" :class="message.is_admin ? 'chat-message-admin' : 'chat-message-self'">
                    <small x-text="message.is_admin ? 'Admin SAKINA' : (name || 'Anda')"></small>
                    <p x-text="message.body"></p>
                    <time :datetime="message.created_at" x-text="formatTime(message.created_at)"></time>
                </div>
            </template>
        </div>
        <p class="chat-error" role="alert" x-show="error" x-text="error" x-cloak></p>
        <form class="chat-compose" @submit.prevent="send()">
            <label class="sr-only" for="chat-body">Pesan untuk admin</label>
            <textarea id="chat-body" x-ref="input" x-model="body" :disabled="sending" maxlength="2000" rows="2" placeholder="Tulis pesan Anda..." @keydown.enter="if (!$event.shiftKey && !$event.isComposing) { $event.preventDefault(); send(); }"></textarea>
            <button type="submit" :disabled="sending || !body.trim()" aria-label="Kirim pesan"><i data-lucide="arrow-up-right"></i></button>
        </form>
        <div class="chat-footer">Riwayat tersimpan · Balasan muncul otomatis</div>
    </section>
    <button type="button" class="chat-launcher" x-ref="launcher" @click="toggle()" :aria-expanded="open" aria-label="Chat dengan admin"><i data-lucide="messages-square"></i><span>Chat Admin</span></button>
</div>
