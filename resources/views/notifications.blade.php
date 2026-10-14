@extends('layouts.app')
@section('title','Notifikasi')
@section('content')
<section class="member-notifications">
    <div class="page-heading">
        <h1>Notifikasi <span class="pill">{{ auth()->user()->memberNotifications()->whereNull('read_at')->count() }} belum dibaca</span></h1>
        <p>Kabar terbaru dari perjalanan menabung Anda.</p>
    </div>
    <div class="notification-list">
        @forelse($notifications as $notification)
            <article class="notification-row {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                <span class="notification-icon"><i data-lucide="{{ $notification->type === 'handover' ? 'gift' : ($notification->type === 'payment' ? 'receipt-text' : 'bell') }}" aria-hidden="true"></i></span>
                <div class="notification-body">
                    <h2>{{ $notification->title }}</h2>
                    <p>{{ $notification->message }}</p>
                    <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                </div>
                @if(!$notification->read_at)
                    <form method="post" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button class="notification-read" aria-label="Tandai dibaca"><i data-lucide="check" aria-hidden="true"></i></button>
                    </form>
                @endif
            </article>
        @empty
            <div class="card empty"><div class="icon-box"><i data-lucide="bell"></i></div><h3>Semua tenang di sini</h3><p>Informasi pembayaran dan tabungan akan muncul di sini.</p></div>
        @endforelse
    </div>
    @if($notifications->total())
        <nav class="notification-pagination" aria-label="Halaman notifikasi">
            <span class="notification-page-summary">{{ $notifications->firstItem() }}&ndash;{{ $notifications->lastItem() }} dari {{ $notifications->total() }} &middot; Halaman {{ $notifications->currentPage() }} / {{ $notifications->lastPage() }}</span>
            <div class="notification-page-actions">
                @if($notifications->onFirstPage())
                    <span class="notification-page-link is-disabled" aria-disabled="true">&larr; Sebelumnya</span>
                @else
                    <a class="notification-page-link" href="{{ $notifications->previousPageUrl() }}" rel="prev">&larr; Sebelumnya</a>
                @endif
                @if($notifications->hasMorePages())
                    <a class="notification-page-link" href="{{ $notifications->nextPageUrl() }}" rel="next">Selanjutnya &rarr;</a>
                @else
                    <span class="notification-page-link is-disabled" aria-disabled="true">Selanjutnya &rarr;</span>
                @endif
            </div>
        </nav>
    @endif
</section>
@endsection
