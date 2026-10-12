<?php

namespace App\Filament\Resources\SavingPlans;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\SavingPlans\Pages\ManageSavingPlans;
use App\Models\SavingPlan;
use App\Services\SavingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SavingPlanResource extends AdminResource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = SavingPlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Tenor Tabungan';

    protected static ?string $modelLabel = 'Tenor Tabungan';

    protected static ?string $pluralModelLabel = 'Tenor Tabungan';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('product_id')->label('Produk')->relationship('product', 'name')->searchable()->preload()->required(), TextInput::make('name')->label('Nama tenor')->required(), TextInput::make('months')->label('Jumlah bulan')->numeric()->minValue(1)->maxValue(120)->required(), TextInput::make('monthly_amount')->label('Cicilan bulanan (Rp)')->numeric()->minValue(1)->helperText('Kosongkan untuk perhitungan otomatis. Cicilan terakhir menyesuaikan sisa harga.'), TextInput::make('down_payment')->label('Uang muka (Rp)')->numeric()->minValue(0)->required()->default(0), TextInput::make('admin_fee')->label('Biaya admin (Rp)')->numeric()->minValue(0)->required()->default(0), Toggle::make('is_active')->label('Aktif')->default(true)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('product.name')->label('Produk')->searchable(), TextColumn::make('name')->label('Tenor'), TextColumn::make('months')->label('Bulan')->sortable(), TextColumn::make('monthly_amount')->label('Cicilan')->money('IDR')->placeholder('Otomatis'), IconColumn::make('is_active')->label('Aktif')->boolean()])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Tenor Tabungan #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageSavingPlans::route('/')];
    }
}
