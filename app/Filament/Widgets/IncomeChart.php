<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\Payment;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class IncomeChart extends ChartWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Tren Pemasukan';

    protected ?string $description = 'Pembayaran disetujui berdasarkan tanggal verifikasi, sesuai periode terpilih (default 6 bulan terakhir).';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $labels = [];
        $values = [];
        $query = $this->filterDates(Payment::query()->where('status', 'approved'), 'approved_at');
        $hasRange = ! empty($this->pageFilters['from']) || ! empty($this->pageFilters['to']);
        if (! $hasRange) {
            $query->whereBetween('approved_at', [now()->subMonthsNoOverflow(5)->startOfMonth(), now()->endOfMonth()]);
        }
        $payments = $query->get(['amount', 'approved_at']);
        $totals = $payments->groupBy(fn (Payment $payment): string => $payment->approved_at->format('Y-m'))->map(fn ($rows): int => (int) $rows->sum('amount'));
        if ($hasRange) {
            foreach ($totals->sortKeys() as $month => $total) {
                $labels[] = Carbon::parse($month.'-01')->translatedFormat('M Y');
                $values[] = $total;
            }
        } else {
            for ($i = 5; $i >= 0; $i--) {
                $date = now()->subMonthsNoOverflow($i);
                $labels[] = $date->translatedFormat('M Y');
                $values[] = $totals[$date->format('Y-m')] ?? 0;
            }
        }

        return ['datasets' => [['label' => 'Pembayaran disetujui (Rp)', 'data' => $values, 'borderColor' => '#087f52', 'backgroundColor' => '#e7f3e2', 'fill' => true, 'tension' => 0.35]], 'labels' => $labels];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<'JS'
        {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: {
                    label: (context) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(context.parsed.y)
                } }
            },
            scales: { y: { beginAtZero: true, ticks: {
                callback: (value) => 'Rp ' + new Intl.NumberFormat('id-ID', { notation: 'compact' }).format(value)
            } }, x: { grid: { display: false } } }
        }
        JS);
    }
}
