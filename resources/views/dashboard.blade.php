@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<div class="dashboard-welcome welcome-illustrated">
    <div class="welcome-copy">
        <h1>Halo, {{ explode(' ',auth()->user()->name)[0] }}!</h1>
        <p>Rawat impian hari ini.<br>Kambing & sembako untuk esok yang lebih baik.</p>
        <a class="btn welcome-cta" href="{{ route('catalog') }}">Yuk, Wujudkan Impian <i data-lucide="arrow-up-right"></i></a>
        <span class="welcome-caption"><i data-lucide="sprout"></i> Sedikit demi sedikit, jadi berarti.</span>
    </div>
    <x-welcome-mockup />
</div>
<div class="stats">@foreach([['wallet','Tabungan Aktif',$savings->where('status','active')->count(),''],['piggy-bank','Total Tabungan','Rp '.number_format($savings->sum('total_paid'),0,',','.'),'blue'],['target','Sisa Target','Rp '.number_format($savings->sum('remaining_amount'),0,',','.'),'orange'],['receipt-text','Pembayaran',$paymentCount,'pink']] as [$i,$l,$v,$c])<div class="card stat"><span class="icon-box {{ $c }}"><i data-lucide="{{ $i }}"></i></span><div class="stat-content"><small>{{ $l }}</small><strong>{{ $v }}</strong></div></div>@endforeach</div>
<x-goal-showcase :products="$products" />
<div class="dashboard-columns"><div><div class="section-title"><div><span class="eyebrow">PERJALANAN ANDA</span><h3 class="mt-2">Tabungan Saya</h3></div><a href="{{ route('savings.index') }}" class="text-link">Lihat semua <i data-lucide="arrow-right"></i></a></div><div class="saving-grid">@foreach($savings->take(2) as $saving)<x-saving-card :saving="$saving" />@endforeach</div>@if($savings->isEmpty())<div class="card empty"><div class="icon-box"><i data-lucide="piggy-bank"></i></div><h3>Impian Anda dimulai di sini</h3><p>Pilih kambing atau sembako dan buat tabungan pertama Anda.</p><a href="{{ route('catalog') }}" class="btn">Pilih Produk <i data-lucide="arrow-right"></i></a></div>@endif
<div class="section-title mt-9"><div><h3>Rekomendasi untuk Anda</h3><p>Temukan tujuan menabung berikutnya.</p></div><a href="{{ route('catalog') }}" class="text-link">Jelajahi <i data-lucide="arrow-right"></i></a></div><div class="product-grid">@foreach($products as $product)<x-product-card :product="$product" />@endforeach</div></div>
<aside>@if($next)<div class="card"><div class="flex items-center gap-3 mb-5"><span class="icon-box orange"><i data-lucide="calendar-days"></i></span><h3 class="text-sm">Cicilan Berikutnya</h3></div><p class="muted text-xs">{{ $next->userSaving->product_name_snapshot }}</p><strong class="block text-2xl text-[#386a43] my-3">Rp {{ number_format($next->amount,0,',','.') }}</strong><p class="muted text-[11px]">Jatuh tempo {{ $next->due_date->translatedFormat('d M Y') }}</p><a href="{{ route('payments.create',$next) }}" class="btn w-full mt-5">Bayar Cicilan <i data-lucide="arrow-right"></i></a></div>@endif<div class="aside-note mt-5"><span class="text-3xl">🌿</span><h3 class="mt-4">Sedikit hari ini,<br>berarti untuk esok.</h3><p>Menabung secara rutin membuat rencana besar terasa lebih ringan.</p></div><div class="card mt-5"><h3 class="text-sm">Perjalanan Menabung</h3><div class="mt-5 space-y-4">@foreach(['Pilih produk & tenor','Bayar cicilan bulanan','Admin verifikasi setoran','Lunas & terima produk'] as $step)<div class="flex gap-3 items-center text-[11px] muted"><span class="w-6 h-6 rounded-full bg-[#eef4e7] grid place-items-center text-[#76a063]">{{ $loop->iteration }}</span>{{ $step }}</div>@endforeach</div></div></aside></div>
@endsection
