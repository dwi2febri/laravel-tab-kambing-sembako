<div class="crm-dashboard-welcome">
    <div>
        <span class="crm-dashboard-eyebrow">SAKINA / OVERVIEW</span>
        <h2>Selamat datang, {{ auth()->user()->name }}.</h2>
        <p>Pantau pertumbuhan tabungan dan kelola kebutuhan anggota dalam satu tempat.</p>
    </div>
    <a class="crm-dashboard-link" href="{{ route('filament.admin.pages.reports') }}">Lihat laporan <x-heroicon-o-arrow-up-right /></a>
</div>
