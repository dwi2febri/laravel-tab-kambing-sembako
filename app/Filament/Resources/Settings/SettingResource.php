<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Settings\Pages\ManageSettings;
use App\Models\Setting;
use App\Services\SavingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SettingResource extends AdminResource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static ?string $modelLabel = 'Pengaturan';

    protected static ?string $pluralModelLabel = 'Pengaturan';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('key')->label('Kunci pengaturan')->required()->unique(ignoreRecord: true)->helperText('brand_name, whatsapp, contact_email, due_reminder_days, grace_period, late_fee, terms'), Textarea::make('value')->label('Nilai')->rows(6)->columnSpanFull(), Select::make('type')->label('Tipe')->options(['string' => 'Teks', 'integer' => 'Angka', 'text' => 'Teks panjang'])->default('string')->required()]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('key')->label('Pengaturan')->searchable(), TextColumn::make('value')->label('Nilai')->limit(70), TextColumn::make('updated_at')->label('Diperbarui')->dateTime('d M Y H:i')])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Pengaturan #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageSettings::route('/')];
    }
}
