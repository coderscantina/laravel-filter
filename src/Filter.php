<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use ReflectionMethod;

class Filter
{
    protected array $filters = [];
    protected array $whitelistedFilters = [];
    protected Builder|\Illuminate\Database\Query\Builder|Relation $builder;

    public function __construct(array $filters)
    {
        // Apply input sanitization for all filter inputs
        $this->filters = $this->sanitizeInput($filters);
    }

    /**
     * Sanitize input data to prevent injection attacks
     *
     * @param array $input
     * @return array
     */
    protected function sanitizeInput(array $input): array
    {
        $sanitized = [];

        foreach ($input as $key => $value) {
            // Ensure keys are alphanumeric + underscore only
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $key)) {
                continue;
            }

            // Handle arrays recursively
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
                continue;
            }

            // Add basic sanitization for values
            if (is_string($value)) {
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Create filter from request
     *
     * @param Request $request
     * @return static
     */
    public static function fromRequest(Request $request)
    {
        return new static($request->all());
    }

    /**
     * Apply filters to query builder
     *
     * @param Builder|\Illuminate\Database\Query\Builder|Relation $builder
     * @return Builder|\Illuminate\Database\Query\Builder|Relation
     */
    public function apply(Builder|\Illuminate\Database\Query\Builder|Relation $builder): Builder|\Illuminate\Database\Query\Builder|Relation
    {
        $this->builder = $builder;
        $filters = $this->getFilters();

        foreach ($filters as $name => $value) {
            // Skip if method not whitelisted (when whitelist is active)
            if (!empty($this->whitelistedFilters) && !in_array($name, $this->whitelistedFilters)) {
                continue;
            }

            if (($requiredParams = $this->getValidMethodParams($name)) !== null) {
                if ($requiredParams || isset($value)) {
                    $this->{$name}($value);
                } else {
                    $this->{$name}();
                }
            }
        }

        return $this->builder;
    }

    /**
     * Check if method exists and is valid
     *
     * @param string $methodName
     * @return int|null Number of required parameters or null if method is invalid
     */
    protected function getValidMethodParams(string $methodName): ?int
    {
        if (method_exists($this, $methodName)) {
            try {
                $reflection = new ReflectionMethod($this, $methodName);

                if ($reflection->isPublic()) {
                    return $reflection->getNumberOfRequiredParameters();
                }
            } catch (\ReflectionException $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Get current builder instance
     *
     * @return Builder|\Illuminate\Database\Query\Builder|Relation
     */
    protected function getBuilder(): Builder|\Illuminate\Database\Query\Builder|Relation
    {
        return $this->builder;
    }

    /**
     * Get filters array
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Set whitelisted filters
     *
     * @param array $filters
     * @return $this
     */
    public function setWhitelistedFilters(array $filters): self
    {
        $this->whitelistedFilters = $filters;
        return $this;
    }
}

