<?php

namespace App\Models\Concerns;

use App\Services\SchoolContext;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToCurrentClass
{
    protected static function bootBelongsToCurrentClass(): void
    {
        static::addGlobalScope('current_class', function (Builder $query) {
            if ($user = auth()->user()) {
                $query->where($query->getModel()->qualifyColumn('class_id'), SchoolContext::forUser($user)?->id);
            }
        });
    }
}
