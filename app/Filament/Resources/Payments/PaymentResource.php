<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Filament\Resources\Payments\Pages\ViewMemberPayments;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class PaymentResource extends AdminResource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-badge';

    protected static ?string $navigationLabel = 'Konfirmasi Pembayaran';

    protected static ?string $pluralModelLabel = 'Konfirmasi Pembayaran';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Payment::where('status', 'pending')->count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('savings');
    }

    public static function table(Table $table): Table
    {
        return $table->poll('10s')->heading(fn (): string => 'Pembayaran anggota - '.Payment::where('status', 'pending')->count().' menunggu konfirmasi')
            ->description('Pilih anggota untuk melihat seluruh produk tabungan dan meninjau transaksinya.')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->withCount('savings')
                ->withCount(['savings as paid_savings_count' => fn (Builder $savings): Builder => $savings->where('remaining_amount', '<=', 0)])
                ->withCount(['payments as pending_payments_count' => fn (Builder $payments): Builder => $payments->where('status', 'pending')])
                ->withSum(['payments as approved_amount' => fn (Builder $payments): Builder => $payments->where('status', 'approved')], 'amount'))
            ->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
                TextColumn::make('name')->label('Anggota')->description(fn (User $record): ?string => $record->phone)->weight('semibold')->searchable()->sortable(),
                TextColumn::make('savings_count')->label('Produk tabungan')->suffix(' tabungan')->sortable(),
                TextColumn::make('saving_status')->label('Status Tabungan')->badge()
                    ->state(function (User $record): string {
                        $paid = (int) $record->paid_savings_count;
                        $unpaid = (int) $record->savings_count - $paid;

                        if ((int) $record->savings_count === 1) {
                            return $paid === 1 ? 'Lunas' : 'Belum lunas';
                        }

                        return $paid.' lunas, '.$unpaid.' belum lunas';
                    })
                    ->color(fn (User $record): string => (int) $record->paid_savings_count === (int) $record->savings_count ? 'success' : 'warning'),
                TextColumn::make('pending_payments_count')->label('Menunggu konfirmasi')->badge()->color(fn (int $state): string => $state > 0 ? 'warning' : 'gray')->sortable(),
                TextColumn::make('approved_amount')->label('Pembayaran disetujui')->default(0)->money('IDR', decimalPlaces: 0)->color('primary')->weight('bold'),
            ])->recordActions([
                Action::make('detail')->label('Lihat Detail')->icon('heroicon-o-eye')->button()
                    ->url(fn (User $record): string => self::getUrl('detail', ['record' => $record])),
            ])->recordUrl(fn (User $record): string => self::getUrl('detail', ['record' => $record]))
            ->defaultSort('name')->striped();
    }

    public static function transactionsTable(Table $table): Table
    {
        return $table->heading('Daftar pembayaran')->description('Periksa bukti transfer sebelum menyetujui atau menolak pembayaran.')->striped()->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
            TextColumn::make('id')->label('ID')->prefix('#')->color('gray')->sortable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('user.name')->label('Anggota')->weight('semibold')->searchable(),
            TextColumn::make('userSaving.product_name_snapshot')->label('Produk')->searchable()->wrap(),
            TextColumn::make('installment.installment_number')->label('Cicilan')->formatStateUsing(fn (int $state): string => 'Ke-'.$state),
            TextColumn::make('amount')->label('Jumlah')->money('IDR', decimalPlaces: 0)->weight('bold')->color('primary'),
            TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn ($state) => ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'][$state])->color(fn ($state) => match ($state) {
                'pending' => 'warning','approved' => 'success',default => 'danger'
            }),
            TextColumn::make('created_at')->label('Dikirim')->dateTime('d M Y')->description(fn (Payment $record): string => $record->created_at->format('H:i'))->sortable(),
            TextColumn::make('rejection_reason')->label('Alasan penolakan')->wrap()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([SelectFilter::make('status')->label('Status')->options(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'])])->recordActions([
            Action::make('proof')->label('Bukti')->icon('heroicon-o-document-magnifying-glass')->button()->outlined()
                ->modalHeading(fn (Payment $record): string => 'Bukti Pembayaran #'.$record->id)
                ->modalWidth('4xl')->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                ->modalContent(fn (Payment $record): View => view('filament.payments.proof', ['payment' => $record])),
            Action::make('approve')->label('Setujui')->icon('heroicon-o-check')->button()->color('success')->requiresConfirmation()->modalHeading('Setujui pembayaran?')->modalDescription('Pastikan rekening, nominal, dan bukti sudah cocok. Setoran akan masuk ke tabungan.')->visible(fn (Payment $record) => $record->status === 'pending')->action(fn (Payment $record) => self::review($record, true)),
            Action::make('reject')->label('Tolak')->icon('heroicon-o-x-mark')->button()->outlined()->color('danger')->schema([Textarea::make('reason')->label('Alasan penolakan')->required()->maxLength(1000)])->visible(fn (Payment $record) => $record->status === 'pending')->action(fn (Payment $record, array $data) => self::review($record, false, $data['reason'])),
        ])->defaultSort('created_at', 'desc');
    }

    private static function review(Payment $record, bool $approved, ?string $reason = null): void
    {
        try {
            app(PaymentService::class)->review(auth()->user(), $record, $approved, $reason);
            Notification::make()->title($approved ? 'Pembayaran disetujui' : 'Pembayaran ditolak')->success()->send();
        } catch (ValidationException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return ['index' => ManagePayments::route('/'), 'detail' => ViewMemberPayments::route('/{record}/detail')];
    }
}
