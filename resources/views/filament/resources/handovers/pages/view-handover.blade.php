<x-filament-panels::page>
    @php
        $handover = $this->record;
        $saving = $handover->userSaving;
        $appointment = $handover->scheduled_at ?? $handover->preferred_at;
        $stage = ['requested' => 1, 'scheduled' => 2, 'completed' => 3][$handover->status];
    @endphp
    <div class="admin-handover-detail" wire:poll.3s="refreshHandover">
        <section class="admin-handover-summary">
            <img src="{{ $saving->product->imageUrl() }}" alt="{{ $saving->product_name_snapshot }}">
            <div><span>TABUNGAN #{{ $saving->id }}</span><h2>{{ $saving->product_name_snapshot }}</h2><p>{{ $saving->user->name }} &middot; {{ $saving->user->phone }}</p></div>
            <span class="admin-handover-stage is-{{ $handover->status }}">{{ ['requested' => 'Diajukan', 'scheduled' => 'Proses Penyerahan', 'completed' => 'Selesai'][$handover->status] }}</span>
        </section>
        <ol class="admin-handover-steps" aria-label="Tahap serah terima">
            @foreach(['Pengajuan pembeli', 'Proses penyerahan', 'Diterima pembeli'] as $label)
                <li class="{{ $stage >= $loop->iteration ? 'is-active' : '' }}"><span>{{ $loop->iteration }}</span><strong>{{ $label }}</strong></li>
            @endforeach
        </ol>
        <div class="admin-handover-grid">
            <section class="admin-handover-panel">
                <header><x-heroicon-o-clipboard-document-list /><div><h2>Rincian pengajuan</h2><p>Jadwal dan lokasi pilihan pembeli.</p></div></header>
                <dl>
                    <div><dt>Nama anggota</dt><dd>{{ $saving->user->name }}</dd></div>
                    <div><dt>Penerima produk</dt><dd>{{ $handover->recipient_name }}</dd></div>
                    <div><dt>Hari dan tanggal</dt><dd>{{ $appointment?->translatedFormat('l, d F Y') ?? 'Belum dilengkapi' }}</dd></div>
                    <div><dt>Jam</dt><dd>{{ $appointment?->format('H:i') ?? '-' }} WIB</dd></div>
                    <div class="is-full"><dt>Alamat / patokan</dt><dd>{{ $handover->location ?? 'Belum dipilih' }}</dd></div>
                    @if($handover->note)<div class="is-full"><dt>Catatan pembeli</dt><dd>{{ $handover->note }}</dd></div>@endif
                    <div><dt>Diajukan</dt><dd>{{ $handover->requested_at->translatedFormat('d M Y H:i') }}</dd></div>
                    @if($handover->completed_at)<div><dt>Diterima pembeli</dt><dd>{{ $handover->completed_at->translatedFormat('d M Y H:i') }}</dd></div>@endif
                </dl>
            </section>
            <section class="admin-handover-panel" wire:key="handover-map-{{ $handover->id }}-{{ $handover->latitude }}-{{ $handover->longitude }}">
                <header><x-heroicon-o-map-pin /><div><h2>Lokasi serah terima</h2><p>Titik lokasi yang dipilih pembeli.</p></div></header>
                @if($handover->latitude !== null && $handover->longitude !== null)
                    <div class="admin-handover-map-wrap" wire:ignore data-handover-detail-map data-lat="{{ $handover->latitude }}" data-lng="{{ $handover->longitude }}" data-key="{{ config('services.google_maps.key') }}">
                        <div class="admin-handover-map-canvas" data-map-canvas></div>
                        <p class="admin-handover-map-status" data-map-status>Memuat peta lokasi...</p>
                    </div>
                    <a class="admin-handover-map-link" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query={{ $handover->latitude }},{{ $handover->longitude }}">Buka di Google Maps <x-heroicon-o-arrow-up-right /></a>
                @else
                    <p class="admin-handover-map-empty">Titik lokasi belum dilengkapi pembeli.</p>
                @endif
            </section>
        </div>
        <section class="admin-handover-confirmation">
            @if($handover->status === 'requested')
                <div><h2>Tinjau dan konfirmasi</h2><p>Periksa rincian pengajuan sebelum memulai proses penyerahan.</p></div>
                {{ $this->confirmAction }}
            @elseif($handover->status === 'scheduled')
                <x-heroicon-o-truck /><div><h2>Dalam proses penyerahan</h2><p>Menunggu pembeli menekan tombol Terima setelah produk benar-benar diterima.</p></div>
            @else
                <x-heroicon-o-check-circle /><div><h2>Serah terima selesai</h2><p>Pembeli sudah mengonfirmasi penerimaan produk.</p></div>
            @endif
        </section>
    </div>
    @vite('resources/js/handover-detail-map.js')
</x-filament-panels::page>
