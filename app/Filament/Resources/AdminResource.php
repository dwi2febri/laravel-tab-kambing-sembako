<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

abstract class AdminResource extends Resource
{
    public static function canViewAny(): bool
    {
        return Gate::allows('admin');
    }

    public static function canView(Model $record): bool
    {
        return Gate::allows('admin');
    }

    public static function canCreate(): bool
    {
        return Gate::allows('admin');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('admin');
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
