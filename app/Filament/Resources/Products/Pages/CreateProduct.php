<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\SavingService;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    use ManagesProductTenors;

    protected static string $resource = ProductResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['slug'] = ProductResource::generateSlug($data['name']);

        return $data;
    }

    protected function afterCreate(): void
    {
        SavingService::log(auth()->user(), 'master.created', 'Produk #'.$this->record->id);
    }

    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('index');
    }
}
