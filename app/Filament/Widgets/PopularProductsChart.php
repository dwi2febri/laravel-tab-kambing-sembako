<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\UserSaving;
use Filament\Widgets\ChartWidget;

class PopularProductsChart extends ChartWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 3;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Produk Paling Diminati';

    protected ?string $description = 'Lima produk dengan tabungan terbanyak pada periode terpilih.';

    protected ?string $maxHeight = '260px';

    protected ?array $options = [
        'indexAxis' => 'y',
        'maintainAspectRatio' => false,
        'plugins' => ['legend' => ['display' => false]],
        'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'y' => ['grid' => ['display' => false]]],
    ];

    protected function getData(): array
    {
        $products = $this->filterDates(UserSaving::query())
            ->selectRaw('product_id, MAX(product_name_snapshot) as name, COUNT(*) as total')
            ->groupBy('product_id')->orderByDesc('total')->orderBy('product_id')->limit(5)->get();

        return [
            'labels' => $products->pluck('name')->all(),
            'datasets' => [[
                'label' => 'Jumlah tabungan',
                'data' => $products->pluck('total')->map(fn ($total): int => (int) $total)->all(),
                'backgroundColor' => ['#168b71', '#438ba1', '#7a9a65', '#d1a55a', '#7983b5'],
                'borderRadius' => 6,
                'maxBarThickness' => 28,
            ]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
