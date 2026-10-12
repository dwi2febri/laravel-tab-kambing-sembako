@php
$inApp = auth()->check() && !request()->routeIs('home','login','register','password.*','terms','about');
$publicMobileNav = !$inApp && !request()->routeIs('login','register','password.*');
$brandName = 'SAKINA';
$nav = [['dashboard','layout-dashboard','Dashboard'],['savings.index','wallet','Tabungan Saya'],['catalog','shopping-basket','Katalog Produk'],['payments.index','receipt-text','Riwayat Pembayaran'],['notifications','bell','Notifikasi'],['profile','user-round','Profil Saya']];
@endphp
<!DOCTYPE html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="theme-color" content="#087f52"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="description" content="Wujudkan kambing dan sembako impian dengan tabungan bulanan yang mudah, transparan, dan terjangkau.">
<title>@yield('title','Beranda') · {{ $brandName }}</title>
<link rel="icon" href="{{ asset('images/icon.svg') }}" type="image/svg+xml"><link rel="manifest" href="{{ asset('manifest.webmanifest') }}"><link rel="apple-touch-icon" href="{{ asset('images/pwa-192.png') }}">
@fonts
@vite(['resources/css/app.css','resources/js/app.js'])</head><body class="{{ $publicMobileNav ? 'public-mobile-navigation' : '' }}">
@if($inApp)
<aside class="sidebar"><a href="{{ route('home') }}" class="brand"><img class="sakina-logo" src="{{ asset('images/sakina-logo.png') }}" alt="SAKINA - Tabungan Kambing &amp; Sembako" width="1774" height="887"></a><p class="side-label">MENU UTAMA</p><nav class="side-links">
@foreach($nav as [$route,$icon,$label])<a href="{{ route($route) }}" class="{{ request()->routeIs($route) || ($route==='savings.index' && request()->routeIs('savings.show','payments.create')) ? 'active' : '' }}"><i data-lucide="{{ $icon }}"></i>{{ $label }}</a>@endforeach
@if(auth()->user()->role==='admin')<a href="/admin"><i data-lucide="shield-check"></i>Panel Admin</a>@endif</nav>
<div class="help-box"><i data-lucide="messages-square"></i><strong class="block mt-2">Butuh bantuan?</strong><p>Kami siap menemani langkah menabung Anda.</p><a class="text-link" href="{{ route('about') }}">Hubungi kami <i data-lucide="arrow-up-right"></i></a><button hidden data-install class="btn soft small mt-3">Pasang aplikasi</button></div></aside>
<div class="app-main"><header class="app-top"><a class="mobile-brand" href="{{ route('dashboard') }}"><img src="{{ asset('images/sakina-logo.png') }}" alt="SAKINA" width="1774" height="887"></a><span class="breadcrumb">Beranda <span class="mx-3">/</span> <strong class="text-[#52734c]">@yield('title')</strong></span><div class="flex items-center gap-5"><a href="{{ route('notifications') }}" aria-label="Notifikasi" class="relative"><i data-lucide="bell"></i>@if(auth()->user()->memberNotifications()->whereNull('read_at')->exists())<span class="absolute -top-2 -right-2 min-w-4 h-4 px-1 text-[9px] text-white bg-[#087f52] rounded-full text-center" aria-label="Notifikasi belum dibaca">{{ auth()->user()->memberNotifications()->whereNull('read_at')->count() }}</span>@endif</a><span class="h-7 w-px bg-[#e4e9df]"></span><a href="{{ route('profile') }}" class="flex items-center gap-3"><span class="avatar">{{ mb_substr(auth()->user()->name,0,1) }}</span><span class="hidden sm:block text-xs"><strong>{{ auth()->user()->name }}</strong><span class="block muted text-[10px] mt-1">Anggota</span></span></a></div></header><main class="app-content">@include('partials.alerts') @yield('content')</main></div>
<nav class="bottom-nav">@foreach([['dashboard','house','Beranda'],['savings.index','wallet','Tabungan'],['catalog','shopping-basket','Katalog'],['notifications','bell','Notifikasi'],['profile','user-round','Profil']] as [$r,$i,$l])<a href="{{ route($r) }}" class="{{ request()->routeIs($r) ? 'active' : '' }}"><i data-lucide="{{ $i }}"></i>{{ $l }}</a>@endforeach</nav>
@else
<header class="public-nav"><div class="container nav-inner"><a href="{{ route('home') }}" class="brand"><img class="sakina-logo" src="{{ asset('images/sakina-logo.png') }}" alt="SAKINA - Tabungan Kambing &amp; Sembako" width="1774" height="887"></a><nav class="nav-links"><a href="{{ route('home') }}" class="{{ request()->routeIs('home')?'active':'' }}">Beranda</a><a href="{{ route('catalog') }}" class="{{ request()->routeIs('catalog')?'active':'' }}">Katalog</a><a href="{{ route('home') }}#cara-kerja">Cara Kerja</a><a href="{{ route('about') }}">Tentang Kami</a></nav><div class="nav-actions">@auth<a href="{{ auth()->user()->role === 'admin' ? route('filament.admin.pages.dashboard') : route('dashboard') }}" class="btn">{{ auth()->user()->role === 'admin' ? 'Panel Admin' : 'Dashboard' }} <i data-lucide="arrow-right"></i></a>@else<a href="{{ route('login') }}" class="btn secondary">Masuk</a><a href="{{ route('register') }}" class="btn">Daftar</a>@endauth</div></div></header>
<main>@if(session('success') || $errors->any())<div class="container pt-5">@include('partials.alerts')</div>@endif @yield('content')</main>
@if(!request()->routeIs('login','register','password.*'))<footer><div class="container footer-inner"><a href="{{ route('home') }}" class="brand"><img class="sakina-logo" src="{{ asset('images/sakina-logo.png') }}" alt="SAKINA - Tabungan Kambing &amp; Sembako" width="1774" height="887"></a><p>© {{ date('Y') }} SAKINA. Tumbuh bersama, wujudkan impian.</p><a href="{{ route('terms') }}">Syarat & Ketentuan</a></div></footer>@endif
@endif
@if($publicMobileNav)
<nav class="bottom-nav public-bottom-nav" aria-label="Menu utama" x-data="{ hash: window.location.hash }" @hashchange.window="hash = window.location.hash">
    <a href="{{ route('home') }}" :class="{ active: @js(request()->routeIs('home')) && hash !== '#cara-kerja' }" :aria-current="@js(request()->routeIs('home')) && hash !== '#cara-kerja' ? 'page' : null"><i data-lucide="house"></i>Beranda</a>
    <a href="{{ route('catalog') }}" class="{{ request()->routeIs('catalog','products.show') ? 'active' : '' }}" @if(request()->routeIs('catalog','products.show')) aria-current="page" @endif><i data-lucide="shopping-basket"></i>Katalog</a>
    <a href="{{ route('home') }}#cara-kerja" :class="{ active: @js(request()->routeIs('home')) && hash === '#cara-kerja' }" :aria-current="@js(request()->routeIs('home')) && hash === '#cara-kerja' ? 'location' : null"><i data-lucide="circle-play"></i>Cara Kerja</a>
    <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif><i data-lucide="info"></i>Tentang Kami</a>
</nav>
@endif
<x-admin-chat :in-app="$inApp" />
</body></html>
