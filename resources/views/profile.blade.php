@extends('layouts.app')
@section('title','Profil Saya')
@section('content')
<div class="page-heading"><h1>Profil Saya</h1><p>Lengkapi informasi agar proses serah terima berjalan lancar.</p></div>
<section class="card profile-account-card">
    <div class="avatar profile-account-avatar">{{ mb_substr(auth()->user()->name,0,1) }}</div>
    <div class="profile-account-info"><h3>{{ auth()->user()->name }}</h3><p>{{ auth()->user()->email }}</p></div>
    <form action="{{ route('logout') }}" method="post">@csrf<button class="btn secondary"><i data-lucide="log-out"></i>Keluar Akun</button></form>
</section>
<form class="card" method="post" action="{{ route('profile.update') }}">
    @csrf @method('put')
    <h3 class="mb-6">Informasi Pribadi</h3>
    @foreach(['name'=>'Nama lengkap','phone'=>'Nomor WhatsApp','address'=>'Alamat'] as $key=>$label)
        <div class="field"><label for="{{ $key }}">{{ $label }}</label><input name="{{ $key }}" id="{{ $key }}" value="{{ old($key,auth()->user()->$key) }}" @required($key!=='address')></div>
    @endforeach
    <div class="field"><label for="profile-email">Email</label><input id="profile-email" type="email" value="{{ auth()->user()->email }}" disabled></div>
    <h3 class="my-6">Ubah Kata Sandi</h3><p class="muted text-xs mb-5">Isi hanya jika ingin mengganti kata sandi.</p>
    @foreach(['current_password'=>'Kata sandi saat ini','password'=>'Kata sandi baru','password_confirmation'=>'Konfirmasi kata sandi baru'] as $key=>$label)
        <div class="field"><label for="{{ $key }}">{{ $label }}</label><input type="password" name="{{ $key }}" id="{{ $key }}" autocomplete="{{ $key==='current_password'?'current-password':'new-password' }}"></div>
    @endforeach
    <button class="btn">Simpan Perubahan <i data-lucide="check"></i></button>
</form>
@endsection
