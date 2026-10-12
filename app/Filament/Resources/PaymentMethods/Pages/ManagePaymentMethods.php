<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Services\SavingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManagePaymentMethods extends ManageRecords
{
    protected static string $resource = PaymentMethodResource::class;

    public function getSubheading(): ?string
    {
        return 'Atur bank, nomor rekening, dan nama pemilik tujuan transfer. Perubahan langsung tampil di halaman pembayaran anggota.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Rekening Pembayaran')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.created', 'Rekening Pembayaran #'.$record->id))];
    }
}
