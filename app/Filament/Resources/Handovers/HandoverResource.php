<?php

namespace App\Filament\Resources\Handovers;

use App\Filament\Resources\AdminResource;
use App\Filament\Resources\Handovers\Pages\ManageHandovers;
use App\Filament\Resources\Handovers\Pages\ViewHandover;
use App\Models\Handover;
use BackedEnum;
use Filament\Actions\Action;
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
        return $table->poll('3s')
            ->heading(fn (): string => 'Serah Terima - '.Handover::whereIn('status', ['requested', 'scheduled'])->count().' perlu ditindaklanjuti')
            ->description(fn (): string => Handover::where('status', 'requested')->count().' menunggu konfirmasi, '.Handover::where('status', 'scheduled')->count().' dalam proses penyerahan.')
            ->columns([
                TextColumn::make('row_number')->label('NO')->rowIndex()->alignCenter()->width('4rem'),
                TextColumn::make('userSaving.user.name')->label('Anggota')->searchable()->weight('semibold')->description(fn (Handover $record): ?string => $record->userSaving->user->phone),
                TextColumn::make('userSaving.product_name_snapshot')->label('Produk')->searchable()->wrap(),
                TextColumn::make('status')->label('Status')->badge()->formatStateUsing(fn (string $state): string => ['requested' => 'Diajukan', 'scheduled' => 'Proses Penyerahan', 'completed' => 'Selesai'][$state] ?? $state)->color(fn (string $state): string => ['requested' => 'warning', 'scheduled' => 'info', 'completed' => 'success'][$state] ?? 'gray'),
                TextColumn::make('preferred_at')->label('Jadwal serah terima')->state(fn (Handover $record) => $record->scheduled_at ?? $record->preferred_at)->dateTime('d M Y H:i')->placeholder('Belum dilengkapi'),
                TextColumn::make('location')->label('Lokasi')->limit(35)->wrap()->placeholder('Belum dipilih'),
                TextColumn::make('requested_at')->label('Diajukan')->dateTime('d M Y')->sortable(),
            ])
            ->recordUrl(fn (Handover $record): string => static::getUrl('view', ['record' => $record]))
            ->filters([SelectFilter::make('status')->options(['requested' => 'Diajukan', 'scheduled' => 'Proses Penyerahan', 'completed' => 'Selesai'])])
            ->recordActions([
                Action::make('detail')->label(fn (Handover $record): string => $record->status === 'requested' ? 'Konfirmasi' : 'Lihat detail')->icon('heroicon-o-eye')->button()->url(fn (Handover $record): string => static::getUrl('view', ['record' => $record])),
            ])->defaultSort('id', 'desc')->emptyStateHeading('Belum ada pengajuan serah terima')->emptyStateIcon('heroicon-o-gift');
    }

    public static function getPages(): array
    {
        return ['index' => ManageHandovers::route('/'), 'view' => ViewHandover::route('/{record}')];
    }
}
