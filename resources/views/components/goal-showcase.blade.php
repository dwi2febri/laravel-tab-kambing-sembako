@props(['products'])
<section class="goal-showcase" aria-label="Inspirasi tabungan">
    <div class="section-title">
        <div><span class="eyebrow">SATU RENCANA, BANYAK KEBAIKAN</span><h3>Mau wujudkan impian yang mana?</h3></div>
        <span class="showcase-note"><i data-lucide="shield-check"></i> Setoran terverifikasi</span>
    </div>
    <div class="goal-showcase-grid">
        @foreach([['goat','Tabungan Kambing','Kambing impian, selangkah lebih dekat.','🐐'],['grocery','Tabungan Sembako','Bekal keluarga, direncanakan dari sekarang.','🌾']] as [$type,$label,$description,$symbol])
        <a class="goal-mockup goal-mockup-{{ $type }}" href="{{ route('catalog',['type'=>$type]) }}">
            <div class="goal-mockup-copy"><span class="goal-type"><span>{{ $symbol }}</span>{{ $label }}</span><h4>{{ $description }}</h4><span class="goal-mockup-link">Temukan pilihan Anda <i data-lucide="arrow-up-right"></i></span></div>
            <div class="goal-mockup-art" aria-hidden="true">
                <span class="mockup-orbit"></span>
                <div class="mockup-product">
                    <img src="{{ asset('images/'.$type.'.webp') }}" alt="" loading="lazy">
                    <span class="mockup-sticker"><i data-lucide="{{ $type === 'goat' ? 'heart' : 'shopping-basket' }}"></i></span>
                </div>
                <span class="mockup-coin coin-one">Rp</span>
                <span class="mockup-coin coin-two">+</span>
                <span class="mockup-sparkle">✦</span>
            </div>
        </a>
        @endforeach
    </div>
</section>
