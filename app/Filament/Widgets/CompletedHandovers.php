<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\Handover;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CompletedHandovers extends TableWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 5;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Serah Terima Selesai')
            ->description('Anggota yang sudah menerima produk, berdasarkan tanggal serah terima selesai.')
            ->query(fn () => $this->filterDates(Handover::query()->with('userSaving.user')->where('status', 'completed'), 'completed_at'))
            ->columns([
                TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
                TextColumn::make('userSaving.user.name')->label('Anggota')->searchable()->weight('semibold'),
                TextColumn::make('userSaving.product_name_snapshot')->label('Produk')->searchable()->wrap(),
                TextColumn::make('recipient_name')->label('Penerima')->searchable(),
                TextColumn::make('completed_at')->label('Tanggal Serah Terima')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('location')->label('Lokasi')->placeholder('-')->wrap(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (): string => 'Selesai')->color('success'),
            ])
            ->defaultSort('completed_at', 'desc')
            ->paginationPageOptions([5, 10, 25])->defaultPaginationPageOption(5)
            ->emptyStateHeading('Belum ada serah terima selesai')
            ->emptyStateDescription('Penyerahan yang selesai pada periode terpilih akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-gift');
    }
}
