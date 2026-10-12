<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Widgets\Concerns\FiltersDashboardDates;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingPayments extends TableWidget
{
    use FiltersDashboardDates;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Antrean Verifikasi Pembayaran')
            ->description('Pembayaran menunggu verifikasi, diurutkan dari yang paling lama.')
            ->query(fn () => $this->filterDates(Payment::query()->with(['user', 'userSaving', 'method'])->where('status', 'pending')))
            ->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
                TextColumn::make('user.name')->label('Anggota')->searchable()->weight('semibold'),
                TextColumn::make('userSaving.product_name_snapshot')->label('Produk')->wrap(),
                TextColumn::make('method.bank_name')->label('Bank'),
                TextColumn::make('amount')->label('Jumlah')->money('IDR', decimalPlaces: 0),
                TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (): string => 'Menunggu')->color('warning'),
            ])
            ->headerActions([Action::make('review')->label('Kelola Pembayaran')->icon('heroicon-o-arrow-top-right-on-square')->url(PaymentResource::getUrl())])
            ->defaultSort('created_at', 'asc')
            ->paginationPageOptions([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Tidak ada antrean pada periode ini')
            ->emptyStateDescription('Pembayaran baru yang menunggu verifikasi akan muncul di sini.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
