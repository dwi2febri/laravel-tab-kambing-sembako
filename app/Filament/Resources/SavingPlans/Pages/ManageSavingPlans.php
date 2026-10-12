<?php

namespace App\Filament\Resources\SavingPlans\Pages;

use App\Filament\Resources\SavingPlans\SavingPlanResource;
use App\Services\SavingService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;

class ManageSavingPlans extends ManageRecords
{
    protected static string $resource = SavingPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Tenor Tabungan')->after(fn (Model $record) => SavingService::log(auth()->user(), 'master.created', 'Tenor Tabungan #'.$record->id))];
    }
}
