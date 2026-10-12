<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\PaymentMethods\Pages\ManagePaymentMethods;
use App\Models\PaymentMethod;
use App\Services\SavingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class PaymentMethodResource extends AdminResource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Rekening Pembayaran';

    protected static ?string $modelLabel = 'Rekening Pembayaran';

    protected static ?string $pluralModelLabel = 'Rekening Pembayaran';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Label rekening')->required()->maxLength(255)->placeholder('Rekening utama'),
            TextInput::make('bank_name')->label('Nama bank')->required()->maxLength(255)->placeholder('BCA / BRI / Mandiri'),
            TextInput::make('account_number')->label('Nomor rekening')->required()->maxLength(50)->inputMode('numeric')->regex('/^[0-9]+$/')->helperText('Masukkan angka tanpa spasi. Angka nol di depan tetap disimpan.'),
            TextInput::make('account_holder')->label('Nama pemilik rekening (a.n.)')->required()->maxLength(255),
            Toggle::make('is_active')->label('Tampilkan ke anggota')->default(true)->helperText('Rekening aktif tersedia sebagai tujuan transfer pada halaman pembayaran anggota.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('name')->label('Label')->searchable(), TextColumn::make('bank_name')->label('Bank')->searchable(), TextColumn::make('account_number')->label('Nomor rekening')->searchable()->copyable(), TextColumn::make('account_holder')->label('Pemilik')->searchable(), IconColumn::make('is_active')->label('Aktif')->boolean()])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Rekening Pembayaran #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManagePaymentMethods::route('/')];
    }
}
