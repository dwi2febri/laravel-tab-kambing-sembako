<x-filament-panels::page>
    <section class="report-filter-card">
        <header><h2>Filter laporan</h2><p>Pilih jenis laporan. Data di bawah diperbarui otomatis sesuai filter.</p></header>
        <form wire:submit="applyFilters">
            <div class="report-filter-grid">
                <div><label for="type">Jenis laporan</label><select id="type" wire:model.live="type">@foreach(['payments'=>'Pembayaran','savings'=>'Tabungan','products'=>'Produk','handovers'=>'Serah Terima','users'=>'Anggota'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div><label for="from">Dari tanggal</label><input type="date" id="from" wire:model.live="from"></div>
                <div><label for="to">Sampai tanggal</label><input type="date" id="to" wire:model.live="to"></div>
                <div><label for="status">Status</label><select id="status" wire:model.live="status"><option value="">Semua status</option>@foreach($this->statusOptions() as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                @if($type !== 'products')<div><label for="member">Anggota</label><select id="member" wire:model.live="userId"><option value="">Semua anggota</option>@foreach($members as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select></div>@endif
                @if($type !== 'users')<div><label for="product">Produk</label><select id="product" wire:model.live="productId"><option value="">Semua produk</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></div>@endif
            </div>
            @if($errors->any())<p role="alert" class="report-error">{{ $errors->first() }} Data di bawah masih memakai filter terakhir yang valid.</p>@endif
            <div class="report-filter-actions"><x-filament::button type="submit" icon="heroicon-o-funnel">Tampilkan data</x-filament::button><x-filament::button color="gray" wire:click="resetFilters">Reset filter</x-filament::button><span wire:loading>Memperbarui laporan...</span></div>
        </form>
    </section>
    <section class="report-results" wire:loading.class="opacity-60">
        <header class="report-results-header">
            <div><h2>{{ $report['title'] }}</h2><p>{{ $rows->total() }} data ditemukan &middot; {{ $appliedFilters['from'] ?? 'Semua tanggal' }} &ndash; {{ $appliedFilters['to'] ?? 'Sekarang' }}</p></div>
            <div class="report-export-actions">
                <x-filament::button tag="a" color="gray" icon="heroicon-o-document-text" :href="route('reports.export', ['format'=>'pdf', ...$appliedFilters])">Unduh PDF</x-filament::button>
                <x-filament::button tag="a" icon="heroicon-o-arrow-down-tray" :href="route('reports.export', ['format'=>'xlsx', ...$appliedFilters])">Unduh Excel</x-filament::button>
            </div>
        </header>
        <div class="report-table-scroll"><table class="report-data-table"><thead><tr>@foreach($report['headers'] as $header)<th>{{ $header }}</th>@endforeach</tr></thead><tbody>
            @forelse($rows as $row)<tr>@foreach($row as $value)<td>{{ $value }}</td>@endforeach</tr>
            @empty<tr><td colspan="{{ count($report['headers']) }}" class="report-empty">Tidak ada data sesuai filter. Coba ubah periode atau status.</td></tr>@endforelse
        </tbody></table></div>
        <footer>{{ $rows->links() }}</footer>
    </section>
</x-filament-panels::page>
