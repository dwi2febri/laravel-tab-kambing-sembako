@extends('layouts.app')
@section('title',match($mode){'register'=>'Daftar','forgot'=>'Lupa Kata Sandi','reset'=>'Atur Kata Sandi',default=>'Masuk'})
@section('content')
<section class="member-auth-page">
<nav class="container member-auth-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><span>/</span><span>{{ $mode === 'register' ? 'Daftar' : 'Masuk' }}</span></nav>
<div class="container auth-grid member-auth">
<aside class="auth-art"><div class="member-auth-intro">
<span class="eyebrow">AKUN ANGGOTA SAKINA</span>
<h2>{{ $mode === 'register' ? 'Langkah kecil, impian besar.' : 'Selamat datang kembali.' }}</h2>
<p>{{ $mode === 'register' ? 'Mulai tabungan kambing dan kebutuhan keluarga dengan lebih terencana.' : 'Lanjutkan menabung dan pantau perkembangan tabungan Anda dalam satu akun.' }}</p>
<div class="member-auth-trust"><i data-lucide="shield-check" aria-hidden="true"></i><span>Tabungan dan riwayat pembayaran dapat diakses setelah masuk ke akun.</span></div>
</div><img src="{{ asset('images/step-save.webp') }}" alt="Ilustrasi anggota SAKINA mulai menabung" width="640" height="607" fetchpriority="high"></aside>
<div class="auth-form"><span class="icon-box mb-6"><i data-lucide="{{ $mode === 'register' ? 'user-round-plus' : 'lock-keyhole' }}" aria-hidden="true"></i></span>
<h1>{{ match($mode){'register'=>'Daftar akun','forgot'=>'Lupa kata sandi?','reset'=>'Buat kata sandi baru',default=>'Masuk akun'} }}</h1>
<p>{{ match($mode){'register'=>'Lengkapi data Anda untuk mulai menabung.','forgot'=>'Masukkan email untuk menerima tautan reset kata sandi.','reset'=>'Gunakan kata sandi baru untuk melindungi akun Anda.',default=>'Gunakan email dan kata sandi yang sudah didaftarkan.'} }}</p>
<form method="post" action="{{ match($mode){'register'=>route('register.submit'),'forgot'=>route('password.email'),'reset'=>route('password.update'),default=>route('login.submit')} }}">@csrf
@if($mode==='reset')<input type="hidden" name="token" value="{{ $token }}">@endif
@if($mode==='register')<div class="field"><label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" placeholder="Nama lengkap Anda"></div><div class="field"><label for="phone">Nomor WhatsApp</label><input id="phone" name="phone" value="{{ old('phone') }}" required autocomplete="tel" placeholder="08xxxxxxxxxx"></div>@endif
<div class="field"><label for="email">Alamat email</label><input id="email" type="email" name="email" value="{{ old('email',$email??'') }}" required autocomplete="email" placeholder="nama@email.com"></div>
@if($mode!=='forgot')<div class="field" x-data="{ visible: false }"><label for="password">Kata sandi</label><div class="relative"><input id="password" :type="visible?'text':'password'" name="password" required autocomplete="{{ $mode==='login'?'current-password':'new-password' }}" placeholder="Minimal 8 karakter" style="padding-right:50px"><button type="button" @click="visible=!visible" class="absolute right-3 top-3" aria-label="Tampilkan kata sandi"><i data-lucide="eye" class="muted"></i></button></div></div>@endif
@if(in_array($mode,['register','reset']))<div class="field"><label for="password_confirmation">Konfirmasi kata sandi</label><input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi"></div>@endif
@if($mode==='register')<label class="flex items-start gap-3 mb-6"><input type="checkbox" name="terms" value="1" required><span class="muted text-[10px] leading-5">Saya menyetujui <a href="{{ route('terms') }}" class="text-[#087f52]" target="_blank">Syarat & Ketentuan</a> tabungan.</span></label>@endif
@if($mode==='login')<div class="flex justify-between items-center mb-6"><label class="flex gap-2 items-center mb-0"><input type="checkbox" name="remember" value="1">Ingat saya</label><a href="{{ route('password.request') }}" class="text-link text-[10px]">Lupa kata sandi?</a></div>@endif
<button class="btn w-full">{{ match($mode){'register'=>'Buat Akun','forgot'=>'Kirim Tautan Reset','reset'=>'Simpan Kata Sandi',default=>'Masuk'} }}<i data-lucide="arrow-right"></i></button></form>
<p class="member-auth-switch">{{ $mode==='register'?'Sudah punya akun?':'Belum punya akun?' }} <a href="{{ $mode==='register'?route('login'):route('register') }}" class="text-[#087f52] font-bold">{{ $mode==='register'?'Masuk':'Daftar sekarang' }}</a></p></div></div></section>
@endsection
