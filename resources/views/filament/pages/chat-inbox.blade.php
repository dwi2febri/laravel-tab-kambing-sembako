<x-filament-panels::page>
    @if(!$selected)
        {{ $this->table }}
    @else
        <div wire:poll.1s>
            <x-filament::button color="gray" icon="heroicon-o-arrow-left" wire:click="closeConversation" class="admin-thread-back">Kembali ke daftar chat</x-filament::button>
            <section class="admin-thread" wire:key="conversation-panel-{{ $selected->id }}">
                <header class="admin-thread-header">
                    <span class="admin-thread-avatar">{{ mb_substr($selected->user?->name ?? 'Pengunjung', 0, 1) }}</span>
                    <div><h2>{{ $selected->user?->name ?? 'Pengunjung #'.$selected->id }}</h2><p>{{ $selected->user ? 'Anggota SAKINA' : 'Pengunjung website' }} &middot; Percakapan #{{ $selected->id }}</p></div>
                    <span class="admin-thread-sync">Pembaruan otomatis</span>
                </header>
                <div class="admin-thread-messages" wire:key="thread-{{ $selected->id }}"
                    x-data="{ follow: true, observer: null, init() { this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight; }); this.observer = new MutationObserver(() => { if (this.follow) this.$nextTick(() => { this.$el.scrollTop = this.$el.scrollHeight; }); }); this.observer.observe(this.$el, { childList: true, subtree: true }); }, destroy() { this.observer.disconnect(); } }"
                    @scroll="follow = $el.scrollHeight - $el.scrollTop - $el.clientHeight < 80">
                    @forelse($messages->getCollection()->reverse() as $message)
                        <article wire:key="message-{{ $message->id }}" class="admin-thread-bubble {{ $message->is_admin ? 'is-admin' : 'is-member' }}">
                            <strong>{{ $message->is_admin ? 'Admin SAKINA' : ($selected->user?->name ?? 'Pengunjung') }}</strong>
                            <p>{{ $message->body }}</p>
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d M Y, H:i') }}</time>
                        </article>
                    @empty
                        <p class="admin-thread-empty">Belum ada pesan dalam percakapan ini.</p>
                    @endforelse
                </div>
                @if($messages->hasPages())<div class="admin-thread-pagination">{{ $messages->links() }}</div>@endif
                <form wire:submit="sendReply" class="admin-thread-compose">
                    <label for="admin-reply">Balas pesan</label>
                    <div class="admin-thread-compose-row">
                        <textarea id="admin-reply" wire:model="reply" maxlength="2000" rows="2" placeholder="Tulis balasan Anda..."></textarea>
                        <x-filament::button type="submit" icon="heroicon-o-paper-airplane" wire:loading.attr="disabled" wire:target="sendReply">Kirim Balasan</x-filament::button>
                    </div>
                    @error('reply')<p role="alert" class="admin-thread-error">{{ $message }}</p>@enderror
                    @error('conversationId')<p role="alert" class="admin-thread-error">{{ $message }}</p>@enderror
                </form>
            </section>
        </div>
    @endif
</x-filament-panels::page>
