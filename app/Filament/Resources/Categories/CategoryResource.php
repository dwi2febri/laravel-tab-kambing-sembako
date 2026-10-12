<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Category;
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

class CategoryResource extends AdminResource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Kategori';

    protected static ?string $modelLabel = 'Kategori';

    protected static ?string $pluralModelLabel = 'Kategori';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->label('Nama')->required(), TextInput::make('slug')->required()->unique(ignoreRecord: true), Select::make('type')->label('Jenis')->options(['goat' => 'Kambing', 'grocery' => 'Sembako'])->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('name')->label('Kategori')->searchable(), TextColumn::make('type')->label('Jenis')->badge()])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Kategori #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageCategories::route('/')];
    }
}
