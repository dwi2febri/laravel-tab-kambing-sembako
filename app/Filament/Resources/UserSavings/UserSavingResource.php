<?php

namespace App\Filament\Resources\UserSavings;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\UserSavings\Pages\ManageUserSavings;
use App\Models\UserSaving;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserSavingResource extends AdminResource
{
    protected static ?string $model = UserSaving::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Kelola Tabungan';

    protected static ?string $pluralModelLabel = 'Tabungan Anggota';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('user.name')->label('Anggota'), TextEntry::make('product_name_snapshot')->label('Produk'), TextEntry::make('product_price_snapshot')->label('Target')->money('IDR'), TextEntry::make('total_paid')->label('Dana masuk')->money('IDR'), TextEntry::make('remaining_amount')->label('Sisa')->money('IDR'), TextEntry::make('tenor_snapshot')->label('Tenor (bulan)'), TextEntry::make('start_date')->label('Dimulai')->date(), TextEntry::make('status')->badge(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
            TextColumn::make('user.name')->label('Anggota')->searchable(), TextColumn::make('product_name_snapshot')->label('Produk')->searchable(), TextColumn::make('tenor_snapshot')->label('Bulan'), TextColumn::make('product_price_snapshot')->label('Target')->money('IDR'), TextColumn::make('total_paid')->label('Terkumpul')->money('IDR'), TextColumn::make('remaining_amount')->label('Sisa')->money('IDR'), TextColumn::make('status')->badge(), TextColumn::make('created_at')->label('Dimulai')->date(),
        ])->filters([SelectFilter::make('status')->options(['active' => 'Aktif', 'paid' => 'Lunas', 'handover_requested' => 'Pengajuan penyerahan', 'handover_scheduled' => 'Terjadwal', 'completed' => 'Selesai'])])->recordActions([ViewAction::make()->label('Detail')])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageUserSavings::route('/')];
    }
}
