<div class="crm-dashboard-filter">
    <form wire:submit="applyDates" class="dashboard-date-filter">
        <div>
            <label for="dashboard-date-from">Dari tanggal</label>
            <x-filament::input.wrapper>
                <x-filament::input type="date" id="dashboard-date-from" wire:model="dateFrom" />
            </x-filament::input.wrapper>
            @error('dateFrom') <p class="dashboard-date-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="dashboard-date-to">Sampai tanggal</label>
            <x-filament::input.wrapper>
                <x-filament::input type="date" id="dashboard-date-to" wire:model="dateTo" />
            </x-filament::input.wrapper>
            @error('dateTo') <p class="dashboard-date-error">{{ $message }}</p> @enderror
        </div>
        <div class="dashboard-date-actions">
            <x-filament::button type="submit" icon="heroicon-o-funnel">Terapkan</x-filament::button>
            <x-filament::button color="gray" wire:click="resetDates">Semua Tanggal</x-filament::button>
        </div>
    </form>
    <p class="dashboard-date-caption">Periode aktif: {{ $this->filters['from'] ?? 'Awal data' }} sampai {{ $this->filters['to'] ?? 'Akhir data' }}.</p>
    <p class="dashboard-date-caption crm-filter-help">Anggota dan tabungan berdasarkan tanggal dibuat; pemasukan berdasarkan tanggal verifikasi; serah terima berdasarkan tanggal selesai.</p>
</div>
