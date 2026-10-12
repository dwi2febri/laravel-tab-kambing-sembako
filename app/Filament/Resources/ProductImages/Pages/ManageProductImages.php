<?php

namespace App\Filament\Resources\ProductImages\Pages;

use App\Filament\Resources\ProductImages\ProductImageResource;
use App\Services\SavingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageProductImages extends ManageRecords
{
    protected static string $resource = ProductImageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Galeri Produk')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.created', 'Galeri Produk #'.$record->id))];
    }
}
