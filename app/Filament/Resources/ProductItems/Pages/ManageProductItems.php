<?php

namespace App\Filament\Resources\ProductItems\Pages;

use App\Filament\Resources\ProductItems\ProductItemResource;
use App\Services\SavingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageProductItems extends ManageRecords
{
    protected static string $resource = ProductItemResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Isi Paket')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.created', 'Isi Paket #'.$record->id))];
    }
}
