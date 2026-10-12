<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\Handover;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserSaving;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SavingsOverview extends StatsOverviewWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 0;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Ringkasan Operasional';

    protected ?string $description = 'Data terkini anggota, tabungan, pembayaran, dan serah terima.';

    protected function getStats(): array
    {
        return [
            Stat::make('Total Anggota', $this->filterDates(User::where('role', 'user'))->count())->icon('heroicon-o-users')->color('success'),
            Stat::make('Tabungan Aktif', $this->filterDates(UserSaving::where('status', 'active'))->count())->icon('heroicon-o-wallet'),
            Stat::make('Menunggu Verifikasi', $this->filterDates(Payment::where('status', 'pending'))->count())->icon('heroicon-o-check-badge')->color('warning'),
            Stat::make('Pembayaran Disetujui', 'Rp '.number_format($this->filterDates(Payment::where('status', 'approved'), 'approved_at')->sum('amount'), 0, ',', '.'))->icon('heroicon-o-banknotes')->color('success'),
            Stat::make('Lunas & Penyerahan', $this->filterDates(UserSaving::whereIn('status', ['paid', 'handover_requested', 'handover_scheduled']))->count())->icon('heroicon-o-gift'),
            Stat::make('Pengajuan Serah Terima', $this->filterDates(Handover::where('status', 'requested'), 'requested_at')->count())->icon('heroicon-o-calendar-days')->color('warning'),
        ];
    }
}
