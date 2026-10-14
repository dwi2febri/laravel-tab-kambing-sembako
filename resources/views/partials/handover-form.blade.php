@php($editingHandover = $editingHandover ?? false)
<div class="card mt-5 handover-card" id="handover-form">
    <div class="handover-form-heading"><span class="handover-status-icon"><i data-lucide="gift" aria-hidden="true"></i></span><div><span class="handover-status-eyebrow">TABUNGAN LUNAS</span><h3>{{ $editingHandover ? 'Ubah pengajuan' : 'Ajukan serah terima' }}</h3></div></div>
    <p class="muted text-xs my-3">Tentukan jadwal dan lokasi yang Anda inginkan untuk dikonfirmasi admin.</p>
    <form method="post" action="{{ route($editingHandover ? 'handovers.update' : 'handovers.request', $saving) }}" data-handover-form data-maps-key="{{ config('services.google_maps.key') }}">
        @csrf
        @if($editingHandover) @method('put') @endif
        <div class="field"><label for="recipient_name">Nama penerima</label><input id="recipient_name" name="recipient_name" maxlength="100" value="{{ old('recipient_name', $editingHandover ? $saving->handover->recipient_name : auth()->user()->name) }}" required></div>
        <div class="field"><label for="preferred_at">Hari, tanggal dan jam</label><input id="preferred_at" type="datetime-local" name="preferred_at" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" value="{{ old('preferred_at', $editingHandover ? $saving->handover->preferred_at?->format('Y-m-d\TH:i') : '') }}" required><p class="handover-help" data-date-day aria-live="polite"></p></div>
        <label>Lokasi serah terima</label>
        <div class="handover-location-actions"><button type="button" class="btn soft small" data-device-location>Lokasi saya saat ini</button><button type="button" class="btn secondary small" data-open-map>Pilih lewat peta</button></div>
        <div class="handover-map-panel" data-map-panel hidden aria-busy="false">
            <div class="handover-map" data-handover-map></div>
            <div class="handover-map-loading" data-map-loading role="status"><span class="handover-map-spinner" aria-hidden="true"></span><span data-map-loading-text>Menyiapkan peta lokasi...</span><button type="button" class="btn soft small" data-map-retry hidden>Coba lagi</button></div>
        </div>
        <p class="handover-help" data-location-status role="status">Gunakan lokasi perangkat atau klik titik di peta.</p>
        <input type="hidden" name="latitude" value="{{ old('latitude', $editingHandover ? $saving->handover->latitude : '') }}"><input type="hidden" name="longitude" value="{{ old('longitude', $editingHandover ? $saving->handover->longitude : '') }}">
        <div class="field"><label for="location">Alamat / patokan lokasi</label><textarea id="location" name="location" rows="2" maxlength="255" required placeholder="Lengkapi alamat dan patokan setelah memilih titik">{{ old('location', $editingHandover ? $saving->handover->location : '') }}</textarea></div>
        <div class="field"><label for="note">Catatan (opsional)</label><textarea id="note" name="note" rows="2" maxlength="1000">{{ old('note', $editingHandover ? $saving->handover->note : '') }}</textarea></div>
        <button class="btn w-full">{{ $editingHandover ? 'Simpan perubahan' : 'Kirim pengajuan' }}</button>
        @if($editingHandover)<a class="handover-edit-cancel" href="{{ route('savings.show', $saving) }}">Batal</a>@endif
    </form>
</div>
