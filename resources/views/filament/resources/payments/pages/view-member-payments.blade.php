<x-filament-panels::page>
    <div class="member-saving-grid">
        @foreach($this->record->savings()->with('product.category')->withCount(['payments', 'payments as pending_count' => fn ($query) => $query->where('status', 'pending')])->get() as $saving)
            <section class="member-saving-card" wire:key="saving-{{ $saving->id }}">
                <div class="member-saving-heading">
                    @if($saving->product)
                        <img src="{{ $saving->product->imageUrl() }}" alt="{{ $saving->product_name_snapshot }}" class="member-saving-photo">
                    @endif
                    <div>
                        <span class="member-saving-caption">TABUNGAN #{{ $saving->id }} &middot; {{ $saving->tenor_snapshot }} BULAN</span>
                        <h2>{{ $saving->product_name_snapshot }}</h2>
                        <span class="member-saving-pending">{{ $saving->pending_count }} pembayaran menunggu konfirmasi</span>
                    </div>
                </div>
                <div class="member-saving-progress-label"><span>Progres tabungan</span><strong>{{ $saving->progress() }}%</strong></div>
                <div class="member-saving-progress" role="progressbar" aria-label="Progres {{ $saving->product_name_snapshot }}" aria-valuenow="{{ $saving->progress() }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ $saving->progress() }}%"></span></div>
                <dl>
                    <div><dt>Sudah dibayar</dt><dd>Rp {{ number_format($saving->total_paid, 0, ',', '.') }}</dd></div>
                    <div><dt>Sisa tabungan</dt><dd>Rp {{ number_format($saving->remaining_amount, 0, ',', '.') }}</dd></div>
                </dl>
                <p class="member-saving-footer">{{ $saving->payments_count }} transaksi &middot; Target Rp {{ number_format($saving->product_price_snapshot, 0, ',', '.') }}</p>
            </section>
        @endforeach
    </div>
    <div class="member-payment-transactions">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
