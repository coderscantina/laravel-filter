<?php

namespace CodersCantina\Filter;

trait AdvancedFilterable
{
    /**
     * Supported operators and their SQL equivalents
     *
     * @var array<string, string>
     */
    protected array $supportedOperators = [
        'null' => 'IS NULL',
        '!null' => 'IS NOT NULL',
        'eq' => '=',
        'neq' => '!=',
        'empty' => '=',
        '!empty' => '!=',
        'like' => 'LIKE',
        '!like' => 'NOT LIKE',
        '^like' => 'LIKE',
        'like$' => 'LIKE',
        'lt' => '<',
        'gt' => '>',
        'lte' => '<=',
        'gte' => '>=',
        'in' => 'IN',
        '!in' => 'NOT IN'
    ];

    /**
     * Apply advanced filter using operator:value syntax
     *
     * @param string $column The column to filter on
     * @param string|array $value The filter value with optional operator prefix
     * @param callable|null $valueTransformer Optional callback to transform the value
     * @return void
     */
    protected function applyAdvancedFilter(string $column, $value, ?callable $valueTransformer = null): void
    {
        if (!$this->isValidColumnName($column)) {
            return;
        }

        if (is_array($value)) {
            $this->applyAdvancedFilterArray($column, $value, $valueTransformer);
            return;
        }

        [$operator, $filterValue] = $this->parseOperatorAndValue($value);
        $this->applyOperator($column, $operator, $filterValue, $valueTransformer);
    }

    /**
     * Apply advanced filter to an array of values
     *
     * @param string $column
     * @param array $values
     * @param callable|null $valueTransformer
     * @return void
     */
    protected function applyAdvancedFilterArray(string $column, array $values, ?callable $valueTransformer = null): void
    {
        $this->builder->where(function ($query) use ($column, $values, $valueTransformer) {
            foreach ($values as $value) {
                [$operator, $filterValue] = $this->parseOperatorAndValue($value);
                $this->applyOperator($column, $operator, $filterValue, $valueTransformer, $query);
            }
        });
    }

    /**
     * Parse the operator and value from a string
     *
     * @param string $value
     * @return array [operator, value]
     */
    protected function parseOperatorAndValue(string $value): array
    {
        $parts = explode(':', $value, 2);

        // If no operator specified or not a supported operator, default to 'eq'
        if (count($parts) !== 2 || !isset($this->supportedOperators[$parts[0]])) {
            return ['eq', $value];
        }

        return [$parts[0], $parts[1]];
    }

    /**
     * Apply the operator to the query
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @param callable|null $valueTransformer
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder|null $query
     * @return void
     */
    protected function applyOperator(
        string    $column,
        string    $operator,
        $value,
        ?callable $valueTransformer = null,
        $query = null
    ): void {
        $query = $query ?? $this->builder;
        $sqlOperator = $this->supportedOperators[$operator] ?? '=';

        switch ($operator) {
            case 'null':
                $query->whereNull($column);
                break;

            case '!null':
                $query->whereNotNull($column);
                break;

            case 'empty':
                $query->where(function ($q) use ($column) {
                    $q->where($column, '')->orWhereNull($column);
                });
                break;

            case '!empty':
                $query->where(function ($q) use ($column) {
                    $q->where($column, '!=', '')->whereNotNull($column);
                });
                break;

            case 'in':
                $values = $this->parseArrayValue($value);
                if ($valueTransformer) {
                    $values = array_map(function ($v) use ($valueTransformer) {
                        return $valueTransformer($v);
                    }, $values);
                }
                $query->whereIn($column, $values);
                break;

            case '!in':
                $values = $this->parseArrayValue($value);
                if ($valueTransformer) {
                    $values = array_map(function ($v) use ($valueTransformer) {
                        return $valueTransformer($v);
                    }, $values);
                }
                $query->whereNotIn($column, $values);
                break;

            case '^like':
                $transformedValue = $valueTransformer ? $valueTransformer($value) : $value;
                $query->where($column, 'LIKE', $transformedValue . '%');
                break;

            case 'like$':
                $transformedValue = $valueTransformer ? $valueTransformer($value) : $value;
                $query->where($column, 'LIKE', '%' . $transformedValue);
                break;

            case 'like':
                $transformedValue = $valueTransformer ? $valueTransformer($value) : $value;
                $query->where($column, 'LIKE', '%' . $transformedValue . '%');
                break;

            case '!like':
                $transformedValue = $valueTransformer ? $valueTransformer($value) : $value;
                $query->where($column, 'NOT LIKE', '%' . $transformedValue . '%');
                break;

            default:
                // Handle standard operators (eq, neq, lt, gt, lte, gte)
                $transformedValue = $valueTransformer ? $valueTransformer($value) : $value;
                $query->where($column, $sqlOperator, $transformedValue);
                break;
        }
    }

    /**
     * Parse array values from comma-separated string
     *
     * @param string $value
     * @return array
     */
    protected function parseArrayValue(string $value): array
    {
        return array_map('trim', explode(',', $value));
    }

    /**
     * Get the list of supported operators
     *
     * @return array
     */
    public function getSupportedOperators(): array
    {
        return array_keys($this->supportedOperators);
    }

    /**
     * Set custom operators
     *
     * @param array $operators
     * @return $this
     */
    public function setCustomOperators(array $operators): self
    {
        $this->supportedOperators = array_merge($this->supportedOperators, $operators);
        return $this;
    }
}
