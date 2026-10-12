<?php

namespace App\Filament\Widgets\Concerns;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

trait FiltersDashboardDates
{
    use InteractsWithPageFilters;

    protected function filterDates(Builder $query, string $column = 'created_at'): Builder
    {
        $dates = $this->pageFilters ?? [];
        $validator = Validator::make($dates, [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...(! empty($dates['from']) ? ['after_or_equal:from'] : [])],
        ]);
        if ($validator->fails()) {
            return $query->whereRaw('1 = 0');
        }
        if (! empty($dates['from'])) {
            $query->where($column, '>=', Carbon::parse($dates['from'])->startOfDay());
        }
        if (! empty($dates['to'])) {
            $query->where($column, '<=', Carbon::parse($dates['to'])->endOfDay());
        }

        return $query;
    }
}
