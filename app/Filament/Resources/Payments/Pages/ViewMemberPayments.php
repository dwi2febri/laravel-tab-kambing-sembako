<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class ViewMemberPayments extends Page implements HasTable
{
    use InteractsWithRecord;
    use InteractsWithTable;

    protected static string $resource = PaymentResource::class;

    protected string $view = 'filament.resources.payments.pages.view-member-payments';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(PaymentResource::canView($this->record), 403);
    }

    public function getTitle(): string
    {
        return 'Pembayaran - '.$this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('back')->label('Kembali ke anggota')->icon('heroicon-o-arrow-left')->url(PaymentResource::getUrl())];
    }

    public function table(Table $table): Table
    {
        return PaymentResource::transactionsTable($table)
            ->query(Payment::query()->where('user_id', $this->record->getKey())->with(['user', 'userSaving', 'installment']))
            ->defaultGroup(Group::make('user_saving_id')->label('Tabungan')->collapsible()
                ->getTitleFromRecordUsing(fn (Payment $record): string => $record->userSaving->product_name_snapshot.' #'.$record->user_saving_id))
            ->heading('Rincian transaksi per tabungan')
            ->description('Transaksi dipisahkan per produk. Klik judul kelompok untuk melipat atau membuka rinciannya.')
            ->emptyStateHeading('Belum ada transaksi pembayaran')
            ->emptyStateDescription('Bukti yang dikirim anggota akan muncul di sini untuk dikonfirmasi.');
    }
}
