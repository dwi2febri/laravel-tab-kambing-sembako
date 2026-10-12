<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFilters;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFilters;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    public function mount(): void
    {
        $this->mountHasFilters();
        $this->dateFrom = $this->filters['from'] ?? null;
        $this->dateTo = $this->filters['to'] ?? null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.pages.dashboard-date-filter'),
            $this->getWidgetsContentComponent(),
        ]);
    }

    public function applyDates(): void
    {
        $this->validate([
            'dateFrom' => ['required', 'date_format:Y-m-d'],
            'dateTo' => ['required', 'date_format:Y-m-d', 'after_or_equal:dateFrom'],
        ], [
            'dateFrom.required' => 'Pilih tanggal awal.',
            'dateTo.required' => 'Pilih tanggal akhir.',
            'dateTo.after_or_equal' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
        ]);
        $this->filters = ['from' => $this->dateFrom, 'to' => $this->dateTo];
        $this->updatedFilters();
    }

    public function resetDates(): void
    {
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->filters = [];
        $this->resetValidation();
        $this->updatedFilters();
    }
}
