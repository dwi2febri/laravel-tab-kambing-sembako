@extends('layouts.app')
@section('title','Tabungan Saya')
@section('content')
<div class="section-title"><div class="page-heading mb-0"><h1>Tabungan Saya</h1><p>Pantau setiap langkah menuju produk impian.</p></div><a class="btn small" href="{{ route('catalog') }}"><i data-lucide="plus"></i>Tabungan Baru</a></div><div class="saving-grid">@foreach($savings as $saving)<x-saving-card :saving="$saving" />@endforeach</div>@if($savings->isEmpty())<div class="card empty"><div class="icon-box"><i data-lucide="wallet"></i></div><h3>Belum ada tabungan</h3><p>Langkah pertama Anda dimulai dengan memilih produk.</p><a class="btn" href="{{ route('catalog') }}">Jelajahi Katalog</a></div>@endif<div class="pagination">{{ $savings->links() }}</div>
@endsection
