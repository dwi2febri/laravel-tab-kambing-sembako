<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Services\SavingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageSettings extends ManageRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Pengaturan')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.created', 'Pengaturan #'.$record->id))];
    }
}
