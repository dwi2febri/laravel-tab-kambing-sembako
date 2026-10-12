<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Services\SavingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class UserResource extends AdminResource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Anggota';

    protected static ?string $modelLabel = 'Anggota';

    protected static ?string $pluralModelLabel = 'Anggota';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->label('Nama lengkap')->required(), TextInput::make('email')->email()->unique(ignoreRecord: true)->required(), TextInput::make('phone')->label('Telepon'), Textarea::make('address')->label('Alamat'), TextInput::make('password')->label('Kata sandi')->password()->minLength(8)->dehydrated(fn ($state) => filled($state))->required(fn (string $operation) => $operation === 'create'), Select::make('role')->label('Peran')->options(['user' => 'Anggota', 'admin' => 'Admin'])->required()->default('user'), Toggle::make('is_active')->label('Aktif')->default(true)]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('name')->label('Nama')->searchable(), TextColumn::make('email')->searchable(), TextColumn::make('phone')->label('Telepon'), TextColumn::make('role')->label('Peran')->badge(), IconColumn::make('is_active')->label('Aktif')->boolean()])->recordActions([EditAction::make()->label('Ubah')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.updated', 'Anggota #'.$record->id))])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageUsers::route('/')];
    }
}
