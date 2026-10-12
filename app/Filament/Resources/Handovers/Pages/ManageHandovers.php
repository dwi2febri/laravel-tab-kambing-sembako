<?php

namespace App\Filament\Resources\Handovers\Pages;

use App\Filament\Resources\Handovers\HandoverResource;
use Filament\Resources\Pages\ManageRecords;

class ManageHandovers extends ManageRecords
{
    protected static string $resource = HandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
