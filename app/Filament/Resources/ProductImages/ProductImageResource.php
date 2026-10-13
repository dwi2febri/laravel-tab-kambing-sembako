<?php

namespace App\Filament\Resources\ProductImages;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\ProductImages\Pages\ManageProductImages;
use App\Models\ProductImage;
use App\Services\SavingService;
use App\Services\WebpImageService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class ProductImageResource extends AdminResource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ProductImage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Galeri Produk';

    protected static ?string $modelLabel = 'Galeri Produk';

    protected static ?string $pluralModelLabel = 'Galeri Produk';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('product_id')->label('Produk')->relationship('product', 'name')->searchable()->preload()->required(), FileUpload::make('path')->label('Gambar')->image()->disk('public')->directory('products/gallery')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => app(WebpImageService::class)->store($file))->maxSize(5120)->required(), TextInput::make('sort_order')->label('Urutan')->numeric()->default(0)->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('product.name')->label('Produk')->searchable(), ImageColumn::make('path')->label('Gambar')->disk('public'), TextColumn::make('sort_order')->label('Urutan')->sortable()])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Galeri Produk #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageProductImages::route('/')];
    }
}
