<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ManageActivityLogs;
use App\Filament\Resources\AdminResource;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ActivityLogResource extends AdminResource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Log Aktivitas';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'), TextColumn::make('user.name')->label('Pelaku')->searchable(), TextColumn::make('action')->label('Aktivitas')->searchable()->badge(), TextColumn::make('description')->label('Keterangan')->wrap(), TextColumn::make('ip_address')->label('IP'), TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageActivityLogs::route('/')];
    }
}
