<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\SavingService;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use ManagesProductTenors;

    protected static string $resource = ProductResource::class;

    protected ?bool $hasDatabaseTransactions = true;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['slug'] = ProductResource::generateSlug($data['name'], $this->record);

        return $data;
    }

    protected function afterSave(): void
    {
        SavingService::log(auth()->user(), 'master.updated', 'Produk #'.$this->record->id);
    }

    protected function getRedirectUrl(): string
    {
        return ProductResource::getUrl('index');
    }
}
