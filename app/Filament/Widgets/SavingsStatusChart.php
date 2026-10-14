<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\UserSaving;
use Filament\Widgets\ChartWidget;

class SavingsStatusChart extends ChartWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 1;

    protected ?string $heading = 'Status Tabungan';

    protected ?string $description = 'Jumlah tabungan pada setiap tahap, berdasarkan tanggal dibuat pada periode terpilih.';

    protected ?string $maxHeight = '260px';

    protected ?array $options = ['maintainAspectRatio' => false, 'plugins' => ['legend' => ['display' => false]], 'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false]]]];

    protected function getData(): array
    {
        $counts = $this->filterDates(UserSaving::query())->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $statuses = ['active', 'paid', 'handover_requested', 'handover_scheduled', 'completed'];

        return ['labels' => ['Aktif', 'Lunas', 'Pengajuan Penyerahan', 'Proses Penyerahan', 'Selesai'], 'datasets' => [[
            'label' => 'Tabungan',
            'data' => array_map(fn (string $status): int => (int) ($counts[$status] ?? 0), $statuses),
            'backgroundColor' => ['#087f52', '#7eb478', '#e7b34b', '#72a8ad', '#245742'],
            'borderRadius' => 8,
            'maxBarThickness' => 56,
        ]]];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
