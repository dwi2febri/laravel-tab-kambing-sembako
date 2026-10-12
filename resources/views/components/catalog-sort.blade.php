@props(['value' => 'popular'])
<div class="catalog-sort" x-data="catalogSort(@js($value))" @click.outside="open = false" @keydown.escape.window="close()">
    <input type="hidden" name="sort" :value="selected" value="{{ $value ?: 'popular' }}">
    <button type="button" class="catalog-sort-trigger" x-ref="trigger" @click="toggle()" @keydown.arrow-down.prevent="openMenu()" @keydown.arrow-up.prevent="openMenu()" aria-label="Urutkan produk" aria-haspopup="listbox" aria-controls="catalog-sort-options" :aria-expanded="open" :class="{ 'is-open': open }">
        <span x-text="label">Pilihan kami</span>
        <svg class="sort-chevron" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="m5 7.5 5 5 5-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <div class="catalog-sort-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <span class="catalog-sort-heading">URUTKAN PRODUK</span>
        <div id="catalog-sort-options" role="listbox" aria-label="Urutan produk" x-ref="list" @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.home.prevent="focusOption(0)" @keydown.end.prevent="focusOption(2)" @keydown.tab="open = false">
            @foreach(['popular' => ['Pilihan kami','Rekomendasi untuk Anda'], 'low' => ['Harga terendah','Dari harga paling kecil'], 'high' => ['Harga tertinggi','Dari harga paling besar']] as $key => [$label,$description])
                <button type="button" class="catalog-sort-option" role="option" tabindex="-1" :aria-selected="selected === @js($key)" :class="{ 'is-selected': selected === @js($key) }" @click="choose(@js($key))">
                    <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                    <span class="catalog-sort-check" x-show="selected === @js($key)"><i data-lucide="check"></i></span>
                </button>
            @endforeach
        </div>
    </div>
</div>
