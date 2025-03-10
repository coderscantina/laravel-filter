<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    /**
     * Apply filter to query
     *
     * @param Builder $query
     * @param Filter $filter
     * @return mixed
    */
    public function scopeFilter(Builder $query, Filter $filter)
    {
        return $filter->apply($query);
    }
}
