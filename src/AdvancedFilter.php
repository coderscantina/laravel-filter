<?php

namespace CodersCantina\Filter;

class AdvancedFilter extends ExtendedFilter
{
    use AdvancedFilterable;

    /**
     * Apply basic or advanced filter based on value format
     *
     * @param string $column
     * @param string|array $value
     * @param callable|null $valueTransformer
     * @return void
     */
    public function applyDynamicFilter(string $column, $value, ?callable $valueTransformer = null): void
    {
        // Check if we have a string value that could contain an operator
        if (is_string($value) && strpos($value, ':') !== false) {
            $this->applyAdvancedFilter($column, $value, $valueTransformer);
        }
        // Check if we have an array of possible operator-prefixed values
        elseif (is_array($value) && !empty($value)) {
            $hasOperator = false;
            foreach ($value as $item) {
                if (is_string($item) && strpos($item, ':') !== false) {
                    $hasOperator = true;
                    break;
                }
            }

            if ($hasOperator) {
                $this->applyAdvancedFilter($column, $value, $valueTransformer);
            } else {
                // Fall back to standard whereIn behavior for arrays without operators
                $transformedValues = $valueTransformer
                    ? array_map($valueTransformer, $value)
                    : $value;
                $this->builder->whereIn($column, $transformedValues);
            }
        }
        // Fall back to simple equals comparison
        else {
            $transformedValue = $valueTransformer ? $valueTransformer($value) : $value;
            $this->builder->where($column, $transformedValue);
        }
    }

    /**
     * Apply range filter with support for advanced operators
     *
     * @param string $column
     * @param string|array $value
     * @param callable|null $valueTransformer
     * @return void
     */
    public function applyAdvancedRangeFilter(string $column, $value, ?callable $valueTransformer = null): void
    {
        // Check if we have an advanced filter
        if (is_string($value) && strpos($value, ':') !== false) {
            $this->applyAdvancedFilter($column, $value, $valueTransformer);
            return;
        }

        // Otherwise use the standard range filter
        parent::applyRangeFilter($column, $value, $valueTransformer);
    }

    /**
     * Apply date filter with support for advanced operators
     *
     * @param string $column
     * @param string|array $value
     * @return void
     */
    public function applyAdvancedDateFilter(string $column, $value): void
    {
        // Check if we have an advanced filter
        if (is_string($value) && strpos($value, ':') !== false) {
            // A bare day means its first second for gte, eq and lt, and its
            // last second for lte and gt, so each operator includes or
            // excludes the whole day.
            [$operator] = $this->parseOperatorAndValue($value);
            $isStart = !in_array($operator, ['lte', 'gt'], true);

            $this->applyAdvancedFilter($column, $value, fn ($date) => $this->formatDate($date, $isStart));
            return;
        }

        // Otherwise use the standard date filter
        parent::applyDateFilter($column, $value);
    }
}
