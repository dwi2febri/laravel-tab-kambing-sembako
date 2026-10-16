<div wire:poll.2s="syncNotifications">
    <x-filament::modal id="admin-notification-popup" width="2xl" teleport="body" sticky-header>
        <x-slot name="trigger">
            <button type="button" class="admin-notification-bell" aria-label="Notifikasi, {{ $actionCount }} perlu ditindaklanjuti, {{ $unreadCount }} riwayat belum dibaca" title="Notifikasi">
                <x-heroicon-o-bell />
                @if($actionCount > 0 || $unreadCount > 0)
                    <span class="admin-notification-count">{{ ($actionCount ?: $unreadCount) > 99 ? '99+' : ($actionCount ?: $unreadCount) }}</span>
                @endif
            </button>
        </x-slot>
        <x-slot name="heading">Notifikasi</x-slot>
        <x-slot name="description">{{ $actionCount }} perlu ditindaklanjuti</x-slot>
        <div class="admin-notification-popup">
            <div class="admin-notification-menu-list">
                @foreach($menus as $menu)
                    <div wire:key="notification-menu-{{ $menu['key'] }}">
                        <button type="button" wire:click="toggleMenu('{{ $menu['key'] }}')" class="admin-notification-menu" aria-expanded="{{ $expandedMenu === $menu['key'] ? 'true' : 'false' }}" aria-controls="notification-details-{{ $menu['key'] }}">
                            <span>{{ $menu['label'] }}</span>
                            <span class="admin-notification-menu-end"><span class="admin-notification-new">{{ $menu['count'] }}</span><x-heroicon-o-chevron-down class="admin-notification-chevron" /></span>
                        </button>
                        @if($expandedMenu === $menu['key'])
                            <div id="notification-details-{{ $menu['key'] }}" class="admin-notification-queue">
                                @forelse($queueDetails as $detail)
                                    <article wire:key="queue-{{ $menu['key'] }}-{{ $detail['id'] }}" class="admin-notification-item">
                                        <h3>{{ $detail['title'] }}</h3>
                                        <p>{{ $detail['message'] }}</p>
                                        <div class="admin-notification-item-actions"><x-filament::button tag="a" :href="$detail['url']" size="sm" color="gray">Lihat detail</x-filament::button></div>
                                    </article>
                                @empty
                                    <p class="admin-notification-history-heading">Tidak ada antrean pada menu ini.</p>
                                @endforelse
                                @if($menu['count'] > 10)
                                    <x-filament::button tag="a" :href="$menu['url']" size="sm">Lihat semua {{ $menu['count'] }} antrean</x-filament::button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="admin-notification-history-heading">Riwayat aktivitas &middot; {{ $unreadCount }} belum dibaca</p>
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
                        <h3>Belum ada riwayat aktivitas</h3>
                        <p>Aktivitas pembayaran, tabungan, dan serah terima baru akan muncul di sini.</p>
                    </div>
                @endforelse
            </div>
            {{ $notifications->links() }}
        </div>
    </x-filament::modal>
</div>
