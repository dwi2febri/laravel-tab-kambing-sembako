@php
    $price = (int) str_replace('.', '', (string) ($this->data['price'] ?? 0));
    $months = max(1, (int) ($this->tenorDraft['months'] ?? 1));
    $fee = (int) str_replace('.', '', (string) ($this->tenorDraft['admin_fee'] ?? 0));
@endphp
<div class="tenor-editor">
    <div class="tenor-editor-form">
        <h3>{{ $this->editingTenor ? 'Ubah tenor' : 'Tambah tenor' }}</h3>
        <label for="tenor-months">Durasi (bulan)</label>
        <x-filament::input.wrapper><x-filament::input id="tenor-months" type="number" min="1" max="120" wire:model.live.debounce.300ms="tenorDraft.months" /></x-filament::input.wrapper>
        @error('tenorDraft.months')<p class="text-danger-600">{{ $message }}</p>@enderror
        <label for="tenor-admin-fee">Biaya administrasi</label>
        <x-filament::input.wrapper prefix="Rp"><x-filament::input id="tenor-admin-fee" type="text" inputmode="numeric" x-data="{}" x-mask:dynamic="$money($input, ',', '.', 0)" wire:model.live.debounce.300ms="tenorDraft.admin_fee" /></x-filament::input.wrapper>
        @error('tenorDraft.admin_fee')<p class="text-danger-600">{{ $message }}</p>@enderror
        <div class="tenor-estimate"><span>Cicilan per bulan</span><strong>Rp {{ number_format(ceil(($price + $fee) / $months), 0, ',', '.') }}</strong><small>(Harga produk + biaya admin) / jumlah bulan. Cicilan terakhir menyesuaikan pembulatan.</small></div>
        <label class="tenor-active"><input type="checkbox" wire:model="tenorDraft.is_active"> Tenor aktif</label>
        <x-filament::button type="button" wire:click="saveTenor">{{ $this->editingTenor ? 'Perbarui tenor' : 'Tambahkan tenor' }}</x-filament::button>
        @if($this->editingTenor)<x-filament::button type="button" color="gray" wire:click="cancelTenor">Batal</x-filament::button>@endif
    </div>
    <div class="tenor-editor-list">
        <h3>Daftar tenor</h3><p>Perubahan disimpan bersama produk. Gunakan Ubah untuk menonaktifkan tenor.</p>
        <div class="tenor-table-scroll"><table><thead><tr><th>NO</th><th>Tenor</th><th>Biaya admin</th><th>Cicilan / bulan</th><th>Status</th><th></th></tr></thead><tbody>
        @forelse(($this->data['plans'] ?? []) as $key => $plan)
            <tr wire:key="tenor-row-{{ $key }}"><td>{{ $loop->iteration }}</td><td>{{ $plan['months'] ?? '-' }} bulan</td><td>Rp {{ number_format((int) str_replace('.', '', (string) ($plan['admin_fee'] ?? 0)),0,',','.') }}</td><td>Rp {{ number_format(ceil(($price + (int) str_replace('.', '', (string) ($plan['admin_fee'] ?? 0))) / max(1, (int) ($plan['months'] ?? 1))),0,',','.') }}</td><td>{{ ($plan['is_active'] ?? true) ? 'Aktif' : 'Nonaktif' }}</td><td><x-filament::button type="button" size="sm" color="gray" wire:click="editTenor('{{ $key }}')">Ubah</x-filament::button></td></tr>
        @empty
            <tr><td colspan="6" class="tenor-empty">Belum ada tenor. Tambahkan melalui form di samping.</td></tr>
        @endforelse
        </tbody></table></div>
        @foreach($errors->getMessages() as $field => $messages)
            @if(str_starts_with($field, 'data.plans.'))<p class="text-danger-600">{{ $messages[0] }}</p>@endif
        @endforeach
    </div>
</div>
