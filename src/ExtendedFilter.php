<?php

namespace CodersCantina\Filter;

use Carbon\Carbon;

class ExtendedFilter extends Filter
{
    use Sortable;

    /**
     * Apply range filter
     *
     * @param string $column
     * @param string $value
     * @param callable|null $cb
     * @return void
     */
    protected function applyRangeFilter(string $column, string $value, callable $cb = null): void
    {
        // Validate column name to prevent SQL injection
        if (!$this->isValidColumnName($column)) {
            return;
        }

        $parts = explode('...', $value);

        foreach ($parts as $idx => $v) {
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

    /**
     * Apply limit to query
     *
     * @param int|string $limit
     * @return void
     */
    public function limit($limit): void
    {
        // Cast to int and ensure it's positive
        $limit = (int) $limit;
        if ($limit > 0) {
            $this->builder->limit($limit);
        }
    }

    /**
     * Apply offset to query
     *
     * @param int|string $offset
     * @return void
     */
    public function offset($offset): void
    {
        // Cast to int and ensure it's positive
        $offset = (int) $offset;
        if ($offset >= 0) {
            $this->builder->offset($offset);
        }
    }

    /**
     * Ensure input is an array
     *
     * @param string|array $ids
     * @return array
     */
    protected function ensureArray($ids): array
    {
        if (!is_array($ids)) {
            if (is_string($ids)) {
                return array_map('trim', explode(',', $ids));
            }
            return [];
        }

        return $ids;
    }

    /**
     * Apply date filter
     *
     * @param string $column
     * @param string $value
     * @return void
     */
    protected function applyDateFilter(string $column, string $value): void
    {
        // Validate column name to prevent SQL injection
        if (!$this->isValidColumnName($column)) {
            return;
        }

        $this->applyRangeFilter($column, $value, [$this, 'formatDate']);
    }

    /**
     * Format date string
     *
     * @param string $date
     * @param bool $isStart
     * @return string
     */
    protected function formatDate(string $date, bool $isStart): string
    {
        // Validate date format to prevent injection
        if (!$this->isValidDateFormat($date)) {
            throw new \InvalidArgumentException("Invalid date format: {$date}");
        }

        if (strlen($date) == 10) {
            $date .= $isStart ? ' 00:00:00' : ' 23:59:59';
        }

        try {
            return Carbon::parse($date)->toDateTimeString();
        } catch (\Exception $e) {
            // Return a safe default if date parsing fails
            return $isStart ? '1970-01-01 00:00:00' : '2099-12-31 23:59:59';
        }
    }

    /**
     * Check if column name is valid to prevent SQL injection
     *
     * @param string $column
     * @return bool
     */
    protected function isValidColumnName(string $column): bool
    {
        // Allow alphanumeric, underscore and dot (for relationships)
        return preg_match('/^[a-zA-Z0-9_\.]+$/', $column);
    }

    /**
     * Check if date format is valid
     *
     * @param string $date
     * @return bool
     */
    protected function isValidDateFormat(string $date): bool
    {
        // Basic date format validation
        return preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $date);
    }
}
