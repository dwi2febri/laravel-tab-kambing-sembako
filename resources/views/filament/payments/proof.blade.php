@php
    $hasProof = $payment->proof_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($payment->proof_path);
    $isPdf = strtolower(pathinfo($payment->proof_path ?? '', PATHINFO_EXTENSION)) === 'pdf';
@endphp
<div class="payment-proof">
    <dl class="payment-proof-details">
        <div><dt>Anggota</dt><dd>{{ $payment->user?->name ?? '-' }}</dd></div>
        <div><dt>Jumlah pembayaran</dt><dd class="payment-proof-amount">Rp {{ number_format($payment->amount, 0, ',', '.') }}</dd></div>
        <div><dt>Produk</dt><dd>{{ $payment->userSaving?->product_name_snapshot ?? '-' }}</dd></div>
        <div><dt>Cicilan / Dikirim</dt><dd>Ke-{{ $payment->installment?->installment_number }} ? {{ $payment->created_at->format('d M Y, H:i') }}</dd></div>
    </dl>
    @if($payment->note)
        <div class="payment-proof-note"><strong>Catatan anggota</strong><p>{{ $payment->note }}</p></div>
    @endif
    <div class="payment-proof-preview">
        @if(!$hasProof)
            <p class="payment-proof-empty">Berkas bukti pembayaran tidak tersedia.</p>
        @elseif($isPdf)
            <object data="{{ route('payments.proof', $payment) }}" type="application/pdf" class="payment-proof-pdf" aria-label="Bukti pembayaran PDF">
                <p>Pratinjau PDF tidak didukung browser ini. Gunakan tombol Buka berkas di bawah.</p>
            </object>
        @else
            <img src="{{ route('payments.proof', $payment) }}" alt="Bukti pembayaran #{{ $payment->id }}" class="payment-proof-image">
        @endif
    </div>
    @if($hasProof)
        <a href="{{ route('payments.proof', $payment) }}" target="_blank" rel="noopener noreferrer" class="payment-proof-open">Buka berkas ukuran penuh ?</a>
    @endif
</div>
