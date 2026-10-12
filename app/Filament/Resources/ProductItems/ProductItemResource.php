<?php

namespace App\Filament\Resources\ProductItems;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\ProductItems\Pages\ManageProductItems;
use App\Models\ProductItem;
use App\Services\SavingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ProductItemResource extends AdminResource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ProductItem::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Isi Paket';

    protected static ?string $modelLabel = 'Isi Paket';

    protected static ?string $pluralModelLabel = 'Isi Paket';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('product_id')->label('Produk')->relationship('product', 'name', fn ($query) => $query->whereHas('category', fn ($q) => $q->where('type', 'grocery')))->searchable()->preload()->required(), TextInput::make('item_name')->label('Nama item')->required(), TextInput::make('qty')->label('Jumlah')->numeric()->minValue(0.01)->required(), TextInput::make('unit')->label('Satuan')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('product.name')->label('Paket')->searchable(), TextColumn::make('item_name')->label('Item'), TextColumn::make('qty')->label('Jumlah'), TextColumn::make('unit')->label('Satuan')])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Isi Paket #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageProductItems::route('/')];
    }
}
