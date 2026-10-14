@php
    $handover = $saving->handover;
    $status = $handover->status;
    $appointment = $handover->scheduled_at ?? $handover->preferred_at;
@endphp
<section class="card mt-5 handover-status-card" aria-label="Status serah terima">
    <header class="handover-status-header">
        <span class="handover-status-icon"><i data-lucide="gift" aria-hidden="true"></i></span>
        <div><span class="handover-status-eyebrow">PENYERAHAN PRODUK</span><h3>Serah Terima</h3></div>
        <span class="handover-status-badge is-{{ $status }}">{{ ['requested' => 'Diajukan', 'scheduled' => 'Proses Penyerahan', 'completed' => 'Selesai'][$status] ?? $status }}</span>
    </header>
    <div class="handover-status-content">
        <div class="handover-status-message is-{{ $status }}">
            <i data-lucide="{{ $status === 'completed' ? 'circle-check' : 'calendar-days' }}" aria-hidden="true"></i>
            <p>{{ ['requested' => 'Menunggu konfirmasi admin.', 'scheduled' => 'Dalam proses penyerahan. Konfirmasi setelah produk benar-benar Anda terima.', 'completed' => 'Produk sudah diserahterimakan. Terima kasih telah menabung!'][$status] ?? '' }}</p>
        </div>
        <dl class="handover-status-details">
            <div><dt>Penerima</dt><dd>{{ $handover->recipient_name }}</dd></div>
            <div><dt>Hari dan tanggal</dt><dd>{{ $appointment?->translatedFormat('l, d F Y') ?? 'Belum dipilih' }}</dd></div>
            @if($appointment)<div><dt>Jam</dt><dd>{{ $appointment->format('H:i') }} {{ config('app.timezone') === 'Asia/Jakarta' ? 'WIB' : '' }}</dd></div>@endif
            @if($handover->location)<div><dt>Lokasi serah terima</dt><dd>{{ $handover->location }}</dd></div>@endif
            @if($handover->note)<div><dt>Catatan</dt><dd>{{ $handover->note }}</dd></div>@endif
        </dl>
        @if($handover->latitude !== null && $handover->longitude !== null)
            <a class="handover-status-map" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&amp;query={{ $handover->latitude }},{{ $handover->longitude }}"><i data-lucide="external-link" aria-hidden="true"></i>Lihat lokasi di Google Maps</a>
        @endif
        @if($status === 'scheduled' && auth()->user()->role === 'user')
            <form method="post" action="{{ route('handovers.receive', $saving) }}" class="handover-receipt-form">
                @csrf
                <label><input type="checkbox" name="received" value="1" required><span>Saya sudah menerima produk sesuai pesanan.</span></label>
                <button class="btn w-full"><i data-lucide="package-check" aria-hidden="true"></i>Terima</button>
            </form>
        @elseif($status === 'completed')
            <p class="handover-received-at">Diterima pada {{ $handover->completed_at?->translatedFormat('d F Y, H:i') }}</p>
        @endif
        @if($status === 'requested'  && auth()->user()->role === 'user')
            <a class="btn soft w-full handover-edit-action" href="{{ route('savings.show', ['saving' => $saving, 'edit_handover' => 1]) }}#handover-form"><i data-lucide="calendar-days" aria-hidden="true"></i>Ubah pengajuan</a>
        @endif
    </div>
    <footer class="handover-status-footer">Diajukan {{ $handover->requested_at->translatedFormat('d M Y, H:i') }}</footer>
</section>
