<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use ReflectionMethod;

class Filter
{
    protected array $filters = [];

    protected Builder|\Illuminate\Database\Query\Builder|Relation $builder;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public static function fromRequest(Request $request)
    {
        return new static($request->all());
    }

    public function apply(Builder|\Illuminate\Database\Query\Builder|Relation $builder
    ): Builder|\Illuminate\Database\Query\Builder|Relation {
        $this->builder = $builder;

        foreach ($this->getFilters() as $name => $value) {
            if (($hasParams = $this->getValidMethodParams($name)) !== null) {
                if ($hasParams || isset($value)) {
                    $this->{$name}($value);
                } else {
                    $this->{$name}();
                }
            }
        }

        return $this->builder;
    }

    protected function getValidMethodParams(string $methodName): ?int
    {
        if (method_exists($this, $methodName)) {
            $reflection = new ReflectionMethod($this, $methodName);

            if ($reflection->isPublic()) {
                return $reflection->getNumberOfRequiredParameters();
            }
        }

        return null;
    }

    protected function getBuilder(): Builder|\Illuminate\Database\Query\Builder|Relation
    {
        return $this->builder;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
