<div wire:poll.10s>
    <x-filament::modal id="admin-notification-popup" width="2xl" teleport="body" sticky-header>
        <x-slot name="trigger">
            <button type="button" class="admin-notification-bell" aria-label="Notifikasi, {{ $unreadCount }} belum dibaca" title="Notifikasi">
                <x-heroicon-o-bell />
                @if($unreadCount > 0)
                    <span class="admin-notification-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>
        </x-slot>
        <x-slot name="heading">Notifikasi</x-slot>
        <x-slot name="description">{{ $unreadCount }} notifikasi belum dibaca</x-slot>
        <div class="admin-notification-popup">
            @if($unreadCount > 0)
                <x-filament::button color="gray" size="sm" wire:click="markAllRead">Tandai semua dibaca</x-filament::button>
            @endif
            <div class="admin-notification-list">
                @forelse($notifications as $notification)
                    <article wire:key="notification-{{ $notification->id }}" class="admin-notification-item {{ $notification->read_at ? '' : 'is-unread' }}">
                        <div class="admin-notification-item-heading">
                            <h3>{{ $notification->title }}</h3>
                            @unless($notification->read_at)<span class="admin-notification-new">Baru</span>@endunless
                        </div>
                        <p>{{ $notification->message }}</p>
                        <time>{{ $notification->created_at->translatedFormat('d M Y, H:i') }}</time>
                        <div class="admin-notification-item-actions">
                            <x-filament::button tag="a" size="sm" color="gray" :href="match ($notification->type) {
                                'payment' => \App\Filament\Resources\Payments\PaymentResource::getUrl(),
                                'handover' => \App\Filament\Resources\Handovers\HandoverResource::getUrl(),
                                default => route('filament.admin.pages.dashboard'),
                            }">Lihat detail</x-filament::button>
                            @unless($notification->read_at)
                                <x-filament::button size="sm" color="gray" wire:click="markRead({{ $notification->id }})">Tandai dibaca</x-filament::button>
                            @endunless
                        </div>
                    </article>
                @empty
                    <div class="admin-notification-empty">
                        <x-heroicon-o-bell />
                        <h3>Belum ada notifikasi</h3>
                        <p>Aktivitas pembayaran, tabungan, dan serah terima baru akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
            {{ $notifications->links() }}
        </div>
    </x-filament::modal>
</div>
