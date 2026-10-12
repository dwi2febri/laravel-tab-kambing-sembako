<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Category;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends AdminResource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $modelLabel = 'Produk';

    protected static ?string $pluralModelLabel = 'Produk';

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Informasi produk')->icon('heroicon-o-cube')->description('Mulai dengan kategori, nama, dan harga produk.')->columns(['default' => 1, 'lg' => 3])->columnSpanFull()->schema([
                Select::make('category_id')->label('Kategori')->relationship('category', 'name')->required()->searchable()->preload()->live(),
                TextInput::make('name')->label('Nama produk')->placeholder('Contoh: Paket Sembako Keluarga')->required()->maxLength(150)->columnSpan(['default' => 1, 'lg' => 2]),
                TextInput::make('slug')->label('Alamat produk (slug)')->placeholder('paket-sembako-keluarga')->helperText('Gunakan huruf kecil dan tanda hubung, tanpa spasi.')->required()->unique(ignoreRecord: true)->maxLength(150),
                TextInput::make('price')->label('Harga produk')->prefix('Rp')->placeholder('250000')->integer()->minValue(1)->required(),
                TextInput::make('stock')->label('Stok tersedia')->suffix('unit')->integer()->minValue(0)->required()->default(0),
                Textarea::make('description')->label('Deskripsi')->placeholder('Jelaskan isi, kualitas, dan informasi penting produk untuk calon anggota.')->required()->rows(3)->columnSpanFull(),
            ]),
            Section::make('Foto produk')->icon('heroicon-o-photo')->description('Unggah maksimal 5 foto, lalu pilih foto yang menjadi sampul katalog.')->columnSpanFull()->schema([
                FileUpload::make('gallery_images')->label('Foto produk')->image()->multiple()->appendFiles()->maxFiles(5)->live()
                    ->imagePreviewHeight('140')->panelLayout('grid')->extraAttributes(['class' => 'product-photo-upload'])
                    ->disk('public')->directory('products/gallery')->maxSize(5120)
                    ->helperText('Maksimal 5 foto, masing-masing 5 MB. Nomor foto mengikuti urutan pada daftar upload.')
                    ->dehydrated(false)
                    ->loadStateFromRelationshipsUsing(function (FileUpload $component, Product $record): void {
                        $component->state(array_values(array_unique(array_filter([
                            $record->thumbnail,
                            ...$record->images()->pluck('path')->all(),
                        ]))));
                    })
                    ->saveRelationshipsUsing(function (FileUpload $component, Product $record, Get $get): void {
                        $paths = array_values($component->getState() ?? []);
                        $cover = $paths[(int) $get('cover_photo')] ?? ($paths[0] ?? null);
                        $record->update(['thumbnail' => $cover]);
                        $gallery = array_values(array_filter($paths, fn (string $path): bool => $path !== $cover));
                        $record->images()->whereNotIn('path', $gallery)->delete();
                        foreach ($gallery as $order => $path) {
                            $record->images()->updateOrCreate(['path' => $path], ['sort_order' => $order]);
                        }
                    }),
                Radio::make('cover_photo')->label('Pilih foto sampul')->default(0)->dehydrated(false)->inline()
                    ->options(function (Get $get): array {
                        $options = [];
                        foreach (array_values($get('gallery_images') ?? []) as $index => $photo) {
                            $options[$index] = 'Foto '.($index + 1);
                        }

                        return $options;
                    })
                    ->helperText('Foto terpilih tampil sebagai sampul di katalog. Jika foto tersebut dihapus, foto pertama digunakan.')
                    ->visible(fn (Get $get): bool => count($get('gallery_images') ?? []) > 0),
            ]),
            Section::make('Detail kambing')->icon('heroicon-o-clipboard-document-list')->description('Lengkapi ciri dan ukuran kambing yang ditawarkan.')->columns(['default' => 1, 'lg' => 3])->columnSpanFull()
                ->visible(fn (Get $get): bool => Category::find($get('category_id'))?->type === 'goat')
                ->schema([
                    TextInput::make('breed')->label('Jenis kambing'),
                    Select::make('gender')->label('Jenis kelamin')->options(['Jantan' => 'Jantan', 'Betina' => 'Betina']),
                    TextInput::make('age_month')->label('Usia (bulan)')->integer()->minValue(0),
                    TextInput::make('weight_min')->label('Berat minimum (kg)')->numeric()->minValue(0),
                    TextInput::make('weight_max')->label('Berat maksimum (kg)')->numeric()->minValue(0)->gte('weight_min'),
                ]),
            Section::make('Isi paket sembako')->icon('heroicon-o-shopping-bag')->description('Tambahkan barang beserta jumlah dan satuannya.')->columnSpanFull()
                ->visible(fn (Get $get): bool => Category::find($get('category_id'))?->type === 'grocery')
                ->schema([
                    Repeater::make('items')->label('Isi paket')->relationship()->columns(3)->defaultItems(0)->addActionLabel('Tambah barang')->reorderable(false)->schema([
                        TextInput::make('item_name')->label('Nama barang')->required()->maxLength(255),
                        TextInput::make('qty')->label('Jumlah')->numeric()->minValue(0.01)->required(),
                        TextInput::make('unit')->label('Satuan')->placeholder('kg, liter, pcs')->required()->maxLength(255),
                    ]),
                ]),
            Section::make('Tenor tabungan')->icon('heroicon-o-calendar-days')->description('Atur pilihan cicilan untuk produk ini. Nonaktifkan tenor yang tidak lagi ditawarkan.')->columnSpanFull()->schema([
                Repeater::make('plans')->label('Pilihan tenor')->relationship()->columns(3)->defaultItems(0)->addActionLabel('Tambah tenor')->reorderable(false)->deletable(false)->schema([
                    TextInput::make('name')->label('Nama tenor')->placeholder('12 bulan')->required()->maxLength(255),
                    TextInput::make('months')->label('Durasi (bulan)')->integer()->minValue(1)->maxValue(120)->required(),
                    TextInput::make('monthly_amount')->label('Cicilan per bulan (Rp)')->integer()->minValue(1)->helperText('Kosongkan untuk perhitungan otomatis.'),
                    TextInput::make('down_payment')->label('Uang muka (Rp)')->integer()->minValue(0)->required()->default(0),
                    TextInput::make('admin_fee')->label('Biaya admin (Rp)')->integer()->minValue(0)->required()->default(0),
                    Toggle::make('is_active')->label('Tenor aktif')->default(true),
                ]),
            ]),
            Section::make('Pengaturan tampilan')->icon('heroicon-o-adjustments-horizontal')->description('Tentukan bagaimana produk ditampilkan kepada anggota.')->columns(2)->columnSpanFull()->schema([
                Toggle::make('is_active')->label('Produk aktif')->helperText('Produk dapat dilihat di katalog.')->default(true),
                Toggle::make('is_featured')->label('Produk pilihan')->helperText('Tandai sebagai produk unggulan.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('name')->label('Produk')->searchable()->sortable(), TextColumn::make('category.name')->label('Kategori'), TextColumn::make('price')->label('Harga')->money('IDR')->sortable(), TextColumn::make('stock')->label('Stok'), IconColumn::make('is_active')->label('Aktif')->boolean()])->recordActions([EditAction::make()->label('Ubah')])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
