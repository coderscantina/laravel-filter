# Filter Package from Coders' Cantina

A secure and optimized filter object for Laravel/Eloquent models based on the laracasts approach.

## Features

- Simple and fluent API for filtering Eloquent models
- Security-focused with protection against SQL injection
- Sortable trait for complex sorting with relation support
- Range filter support for dates and numeric values
- Performance optimizations for large datasets
- Filter whitelisting for controlled access
- Comprehensive test suite

## Getting Started

* Install this package
* Define your filters
* Apply them to your models

## Install

Require this package with composer:

```bash
$ composer require coderscantina/filter
```

## Basic Usage

Define a filter:

```php
<?php namespace App;
 
use CodersCantina\Filter\ExtendedFilter;
 
class TestFilter extends ExtendedFilter
{
    public function name($name)
    {
        return $this->builder->where('name', $name);        
    }
    
    public function latest()
    {
        return $this->builder->latest();        
    }
}
```

In your model:

```php
<?php namespace App;
 
use Illuminate\Database\Eloquent\Model;
use CodersCantina\Filter\Filterable;
 
class TestModel extends Model
{
    use Filterable;
}
```

In your controller:

```php
<?php namespace App\Http\Controllers;
 
use App\TestModel;
use App\TestFilter;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
 
class LessonsController extends Controller
{
    /**
     * Show all lessons.
     *
     * @param  Request $request
     * @return Collection
     */
    public function index(Request $request)
    {
        $filter = new TestFilter($request->all());
        
        // For enhanced security, whitelist allowed filters
        $filter->setWhitelistedFilters(['name', 'latest']);

        return TestModel::filter($filter)->get();
    }
}
```

## Security Features

### Filter Whitelisting

To enhance security, always specify which filters are allowed:

```php
$filter->setWhitelistedFilters(['name', 'price', 'category']);
```

### Input Sanitization

The package automatically sanitizes input to prevent SQL injection attacks. However, you should still validate your input in controllers using Laravel's validation system.

## Advanced Features

### Sortable Trait

The `Sortable` trait which is included in the `ExtendedFilter` offers sorting abilities:

```php
['sort' => '+foo,-bar']; // -> order by foo asc, bar desc
```

Sort using foreign key relations:

```php
['sort' => '+foo.bar']; // -> left join x on x.id = foo.id order by foo.bar asc
```

Limit the number of sort columns for performance:

```php
$filter->setMaxSortColumns(3);
```

Restrict sortable columns:

```php
protected array $sortableColumns = ['name', 'price', 'created_at'];
```

### Range Filters

Apply range filters in various formats:

```php
['price' => '10...']; // -> price >= 10
['price' => '...50']; // -> price <= 50
['price' => '10...50']; // -> price >= 10 and price <= 50
```

### Date Range Filters

Filter by date ranges with automatic formatting:

```php
['created_at' => '2023-01-01...']; // -> created_at >= '2023-01-01 00:00:00'
['created_at' => '...2023-12-31']; // -> created_at <= '2023-12-31 23:59:59'
['created_at' => '2023-01-01...2023-12-31']; // -> Between Jan 1 and Dec 31, 2023
```

### Pagination Support

Apply limit and offset for pagination:

```php
['limit' => 10, 'offset' => 20]; // -> LIMIT 10 OFFSET 20
```

## Performance Optimizations

The package includes several optimizations:

- Join caching for repeated relation sorting
- Maximum sort column limits
- Efficient array handling
- Targeted query building

## Extending

### Custom Filter Methods

Create custom filter methods in your filter class:

```php
public function active($value = true)
{
    $this->builder->where('active', $value);
}

public function priceRange($value)
{
    $this->applyRangeFilter('price', $value);
}

public function dateCreated($value)
{
    $this->applyDateFilter('created_at', $value);
}
```

### Override Core Methods

You can override core methods for custom behavior:

```php
protected function isValidColumnName(string $column): bool
{
    // Your custom validation logic
    return parent::isValidColumnName($column) && in_array($column, $this->allowedColumns);
}
```

## Testing

The package includes a comprehensive test suite:

```bash
$ composer test
```

## Security Best Practices

1. Always use filter whitelisting with `setWhitelistedFilters()`
2. Validate input in your controllers
3. Limit sortable columns to prevent performance issues
4. Use type-hinting in your filter methods
5. Test your filters thoroughly

## Change Log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.
