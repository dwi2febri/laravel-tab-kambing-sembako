<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Handovers\HandoverResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\MemberNotification;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class AdminNotifications extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.admin-notifications';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Notifikasi';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return Gate::allows('admin');
    }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canAccess()) {
            return null;
        }
        $count = auth()->user()->memberNotifications()->whereNull('read_at')->count();

        return $count ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table->query(fn () => MemberNotification::where('user_id', auth()->id()))
            ->heading(fn (): string => 'Notifikasi - '.auth()->user()->memberNotifications()->whereNull('read_at')->count().' belum dibaca')
            ->poll('10s')->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('number')->label('NO')->rowIndex(),
                TextColumn::make('title')->label('Aktivitas')->searchable()->weight('semibold')->wrap(),
                TextColumn::make('message')->label('Keterangan')->wrap(),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                TextColumn::make('read_at')->label('Status')->state(fn (MemberNotification $record): string => $record->read_at ? 'Dibaca' : 'Baru')->badge()->color(fn (string $state): string => $state === 'Baru' ? 'warning' : 'gray'),
            ])
            ->filters([SelectFilter::make('type')->label('Jenis')->options(['payment' => 'Pembayaran', 'handover' => 'Serah terima', 'userSaving' => 'Tabungan'])])
            ->recordActions([
                Action::make('read')->label('Tandai dibaca')->visible(fn (MemberNotification $record): bool => $record->read_at === null)
                    ->action(function (MemberNotification $record): void {
                        Gate::authorize('admin');
                        abort_unless((int) $record->user_id === (int) auth()->id(), 403);
                        $record->update(['read_at' => now()]);
                    }),
                Action::make('open')->label('Lihat')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (MemberNotification $record): string => match ($record->type) {
                    'payment' => PaymentResource::getUrl(),
                    'handover' => HandoverResource::getUrl(),
                    default => route('filament.admin.pages.dashboard'),
                }),
            ])->headerActions([
                Action::make('readAll')->label('Tandai semua dibaca')->action(function (): void {
                    Gate::authorize('admin');
                    auth()->user()->memberNotifications()->whereNull('read_at')->update(['read_at' => now()]);
                }),
            ])->emptyStateHeading('Belum ada notifikasi')->emptyStateDescription('Aktivitas pembayaran, tabungan, dan serah terima baru akan muncul di sini.');
    }
}
