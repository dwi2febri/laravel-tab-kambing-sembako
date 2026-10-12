<?php

namespace App\Filament\Resources\UserSavings\Pages;

use App\Filament\Resources\UserSavings\UserSavingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUserSavings extends ManageRecords
{
    protected static string $resource = UserSavingResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
