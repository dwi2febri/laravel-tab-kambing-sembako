<?php

namespace App\Filament\Resources\Handovers\Pages;

use App\Filament\Resources\Handovers\HandoverResource;
use App\Services\HandoverService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ViewHandover extends Page
{
    use InteractsWithRecord;

    protected static string $resource = HandoverResource::class;

    protected string $view = 'filament.resources.handovers.pages.view-handover';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        abort_unless(HandoverResource::canView($this->record), 403);
        $this->record->load('userSaving.user', 'userSaving.product.category');
    }

    public function getTitle(): string
    {
        return 'Detail Serah Terima #'.$this->record->id;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('back')->label('Kembali ke daftar')->icon('heroicon-o-arrow-left')->color('gray')->url(HandoverResource::getUrl())];
    }

    public function refreshHandover(): void
    {
        Gate::authorize('admin');
        $this->record->refresh()->load('userSaving.user', 'userSaving.product.category');
    }

    public function confirmAction(): Action
    {
        return Action::make('confirm')->label('Konfirmasi serah terima')->icon('heroicon-o-check-badge')->requiresConfirmation()
            ->modalHeading('Konfirmasi pengajuan pembeli?')->modalDescription('Pengajuan akan masuk proses penyerahan. Status selesai setelah pembeli mengonfirmasi produk sudah diterima.')
            ->visible(fn (): bool => $this->record->status === 'requested')
            ->action(function (): void {
                Gate::authorize('admin');
                $this->record->refresh();
                if (! $this->record->preferred_at || ! $this->record->location || $this->record->latitude === null || $this->record->longitude === null) {
                    throw ValidationException::withMessages(['handover' => 'Pembeli perlu melengkapi jadwal dan titik lokasi sebelum dikonfirmasi.']);
                }
                app(HandoverService::class)->schedule(auth()->user(), $this->record, $this->record->preferred_at->toDateTimeString(), $this->record->location);
                $this->refreshHandover();
                $this->dispatch('refresh-sidebar');
                Notification::make()->title('Pengajuan dikonfirmasi. Produk dalam proses penyerahan.')->success()->send();
            });
    }
}
