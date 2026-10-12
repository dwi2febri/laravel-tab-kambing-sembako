<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class PaymentStatusChart extends ChartWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Status Pembayaran';

    protected ?string $description = 'Jumlah pembayaran menurut hasil verifikasi, berdasarkan tanggal dibuat pada periode terpilih.';

    protected ?string $maxHeight = '280px';

    protected ?array $options = ['maintainAspectRatio' => false, 'cutout' => '72%', 'plugins' => ['legend' => ['position' => 'bottom']]];

    protected function getData(): array
    {
        $counts = $this->filterDates(Payment::query())->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return ['labels' => ['Disetujui', 'Menunggu', 'Ditolak'], 'datasets' => [[
            'label' => 'Pembayaran',
            'data' => [(int) ($counts['approved'] ?? 0), (int) ($counts['pending'] ?? 0), (int) ($counts['rejected'] ?? 0)],
            'backgroundColor' => ['#087f52', '#e7b34b', '#dc7865'],
            'borderWidth' => 0,
        ]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function isEmpty(): bool
    {
        return array_sum($this->getCachedData()['datasets'][0]['data']) === 0;
    }
}
