<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait Sortable
{
    protected array $sortableColumns = [];

    protected array $sortColumns = [];

    protected function translateSortString(string $sortString): string
    {
        return $sortString;
    }

    public function sort($sortString = null): void
    {
        if (!$sortString) {
            return;
        }

        $this->sortColumns = [];
        $query = $this->getBuilder();
        $sortColumns = $this->getSortableColumns();

        foreach (explode(',', $sortString) as $sortItem) {
            $sortItem = $this->translateSortString($sortItem);
            // remove sort order and split into qualified column name array
            // +model.relation.column_name -> [model, relation, column_name]
            $path = explode('.', str_replace(['+', '-'], '', $sortItem));
            // extract column
            $column = array_pop($path);

            if (empty($path)) {
                $path = [$query->getModel()->getTable()];
            }

            if (count($sortColumns) && !in_array($column, $sortColumns)) {
                continue;
            }

            $column = $this->applyToQuery($path, $query, $column, $sortItem);
            $this->sortColumns[] = $column;
        }
    }

    public function getSortColumns(): array
    {
        return $this->sortColumns;
    }

    protected function getSortableColumns(): array
    {
        return $this->sortableColumns;
    }

    protected function prepareColumn(array $path, $query, string $column): string
    {
        if (count($path) != 1) {
            return $column;
        }

        if (method_exists($query->getModel()->newInstance(), $path[0])) {
            /** @var \Illuminate\Database\Eloquent\Relations\Relation $relation */
            if ($relation = $query->getRelation($path[0])) {
                if ($relation instanceof BelongsTo) {
                    $query->leftJoin(
                        $relation->getRelated()->getTable(),
                        $relation->getQualifiedForeignKeyName(),
                        '=',
                        $relation->getQualifiedOwnerKeyName()
                    );
                } else {
                    $query->leftJoin(
                        $relation->getRelated()->getTable(),
                        $relation->getQualifiedForeignKeyName(),
                        '=',
                        $relation->getQualifiedParentKeyName()
                    );
                }

                return $relation->getRelated()->getTable() . '.' . $column;
            }
        }

        return $path[0] . '.' . $column;
    }

    protected function applyToQuery(array $path, $query, string $column, string $sortItem): string
    {
        $column = $this->prepareColumn($path, $query, $column);
        $direction = str_starts_with($sortItem, '-') ? 'desc' : 'asc';
        $query->orderBy($column, $direction);

        return $column;
    }
}
