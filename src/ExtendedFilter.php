<?php

namespace CodersCantina\Filter;

use Carbon\Carbon;

class ExtendedFilter extends Filter
{
    use Sortable;

    protected function applyRangeFilter(string $column, string $value, callable $cb = null): void
    {
        foreach (explode('...', $value) as $idx => $v) {
            if (!$v) {
                continue;
            }

            $isStart = $idx === 0;
            $this->builder->where(
                $column,
                $isStart ? '>=' : '<=',
                $cb ? $cb($v, $isStart) : $v
            );
        }
    }

    public function limit($limit): void
    {
        $this->builder->limit($limit);
    }

    public function offset($offset): void
    {
        $this->builder->offset($offset);
    }

    protected function ensureArray($ids): array
    {
        return !is_array($ids) ? explode(',', $ids) : $ids;
    }

    protected function applyDateFilter(string $column, string $value): void
    {
        $this->applyRangeFilter($column, $value, [$this, 'formatDate']);
    }

    protected function formatDate(string $date, bool $isStart): string
    {
        if (strlen($date) == 10) {
            $date .= $isStart ? ' 00:00:00' : ' 23:59:59';
        }

        return Carbon::parse($date)
            ->toDateTimeString();
    }
}
