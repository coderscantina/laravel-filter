<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

trait Sortable
{
    protected array $sortableColumns = [];

    protected array $sortColumns = [];

    /**
     * Maximum number of sort columns to prevent performance issues
     */
    protected int $maxSortColumns = 5;

    /**
     * Translate sort string
     *
     * @param string $sortString
     * @return string
     */
    protected function translateSortString(string $sortString): string
    {
        return $sortString;
    }

    /**
     * Apply sorting
     *
     * @param string|null $sortString
     * @return void
     */
    public function sort($sortString = null): void
    {
        if (!$sortString || !is_string($sortString)) {
            return;
        }

        $this->sortColumns = [];
        $query = $this->getBuilder();
        $sortColumns = $this->getSortableColumns();

        // Prevent excessive sorting to avoid performance issues
        $sortItems = array_slice(explode(',', $sortString), 0, $this->maxSortColumns);

        foreach ($sortItems as $sortItem) {
            $sortItem = $this->translateSortString($sortItem);

            // Validate sort item format
            if (!preg_match('/^[\+\-]?[a-zA-Z0-9_\.]+$/', $sortItem)) {
                continue;
            }

            // Remove sort order and split into qualified column name array
            // +model.relation.column_name -> [model, relation, column_name]
            $path = explode('.', str_replace(['+', '-'], '', $sortItem));

            // extract column
            $column = array_pop($path);

            // Validate column name
            if (!$this->isValidColumnName($column)) {
                continue;
            }

            if (empty($path)) {
                $path = [$query->getModel()->getTable()];
            }

            if (count($sortColumns) && !in_array($column, $sortColumns)) {
                continue;
            }

            try {
                $column = $this->applyToQuery($path, $query, $column, $sortItem);
                $this->sortColumns[] = $column;
            } catch (\Exception $e) {
                // Skip this sort column if an error occurs
                continue;
            }
        }
    }

    /**
     * Get sort columns
     *
     * @return array
     */
    public function getSortColumns(): array
    {
        return $this->sortColumns;
    }

    /**
     * Get sortable columns
     *
     * @return array
     */
    protected function getSortableColumns(): array
    {
        return $this->sortableColumns;
    }

    /**
     * Set max sort columns
     *
     * @param int $max
     * @return $this
     */
    public function setMaxSortColumns(int $max): self
    {
        $this->maxSortColumns = $max > 0 ? $max : 5;
        return $this;
    }

    /**
     * Prepare column for query
     *
     * @param array $path
     * @param mixed $query
     * @param string $column
     * @return string
     */
    protected function prepareColumn(array $path, $query, string $column): string
    {
        // Validate path elements to prevent SQL injection
        foreach ($path as $segment) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $segment)) {
                throw new \InvalidArgumentException("Invalid path segment: {$segment}");
            }
        }

        if (count($path) != 1) {
            return $column;
        }

        if (method_exists($query->getModel()->newInstance(), $path[0])) {
            try {
                /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
                if ($relation = $query->getRelation($path[0])) {
                    $relatedTable = $relation->getRelated()->getTable();

                    // Cache result for performance
                    $cacheKey = 'join_' . $relatedTable;
                    if (!property_exists($this, $cacheKey)) {
                        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                            $query->leftJoin(
                                $relatedTable,
                                $relation->getQualifiedForeignKeyName(),
                                '=',
                                $relation->getQualifiedOwnerKeyName()
                            );
                        } else {
                            $query->leftJoin(
                                $relatedTable,
                                $relation->getQualifiedForeignKeyName(),
                                '=',
                                $relation->getQualifiedParentKeyName()
                            );
                        }
                        $this->{$cacheKey} = true;
                    }

                    return $relatedTable . '.' . $column;
                }
            } catch (\Exception $e) {
                // If relation fetch fails, fallback to default
            }
        }

        return $path[0] . '.' . $column;
    }

    /**
     * Apply to query
     *
     * @param array $path
     * @param Builder|Relation $query
     * @param string $column
     * @param string $sortItem
     * @return string
     */
    protected function applyToQuery(array $path, $query, string $column, string $sortItem): string
    {
        $column = $this->prepareColumn($path, $query, $column);
        $direction = str_starts_with($sortItem, '-') ? 'desc' : 'asc';

        $this->applyOrderBy($query, $column, $direction);

        return $column;
    }

    /**
     * Apply order by to query
     *
     * @param Builder|Relation $query
     * @param string $column
     * @param string $direction
     * @return void
     */
    protected function applyOrderBy($query, string $column, string $direction): void
    {
        // Ensure direction is only 'asc' or 'desc'
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($column, $direction);
    }

    /**
     * Check if column name is valid
     *
     * @param string $column
     * @return bool
     */
    protected function isValidColumnName(string $column): bool
    {
        return preg_match('/^[a-zA-Z0-9_]+$/', $column);
    }
}
