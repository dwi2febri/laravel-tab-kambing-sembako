<?php

namespace App\Filament\Resources\Handovers;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Handovers\Pages\ManageHandovers;
use App\Models\Handover;
use App\Services\HandoverService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class HandoverResource extends AdminResource
{
    protected static ?string $model = Handover::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Serah Terima';

    protected static ?string $pluralModelLabel = 'Serah Terima';

    protected static string|UnitEnum|null $navigationGroup = 'Operasional';

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) {
            return null;
        }
        $count = Handover::whereIn('status', ['requested', 'scheduled'])->count();

        return $count ? (string) $count : null;
    }

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
        return $table->poll('10s')->heading(fn (): string => 'Serah Terima - '.Handover::whereIn('status', ['requested', 'scheduled'])->count().' perlu ditindaklanjuti')->description(fn (): string => Handover::where('status', 'requested')->count().' pengajuan menunggu jadwal, '.Handover::where('status', 'scheduled')->count().' menunggu penyerahan.')->columns([TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
            TextColumn::make('userSaving.user.name')->label('Anggota')->searchable(),
            TextColumn::make('userSaving.product_name_snapshot')->label('Produk')->searchable(),
            TextColumn::make('recipient_name')->label('Penerima'), TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => ['requested' => 'Diajukan', 'scheduled' => 'Terjadwal', 'completed' => 'Selesai'][$state] ?? $state),
            TextColumn::make('requested_at')->label('Pengajuan')->dateTime('d M Y'),
            TextColumn::make('scheduled_at')->label('Jadwal')->dateTime('d M Y H:i'), TextColumn::make('location')->label('Lokasi')->wrap(),
        ])->filters([SelectFilter::make('status')->options(['requested' => 'Diajukan', 'scheduled' => 'Terjadwal', 'completed' => 'Selesai'])])->recordActions([
            Action::make('schedule')->label('Jadwalkan')->visible(fn (Handover $record) => $record->status === 'requested')->schema([DateTimePicker::make('date')->label('Tanggal & waktu')->required()->minDate(now()), TextInput::make('location')->label('Lokasi')->required()->maxLength(255)])->action(function (Handover $record, array $data) {
                app(HandoverService::class)->schedule(auth()->user(), $record, $data['date'], $data['location']);
                Notification::make()->title('Serah terima dijadwalkan')->success()->send();
            }),
            Action::make('complete')->label('Selesaikan')->color('success')->visible(fn (Handover $record) => $record->status === 'scheduled')->requiresConfirmation()->action(function (Handover $record) {
                app(HandoverService::class)->complete(auth()->user(), $record);
                Notification::make()->title('Serah terima selesai')->success()->send();
            }),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ManageHandovers::route('/')];
    }
}
