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
## Advanced Operators

The package supports advanced filtering with operator-based syntax for more expressive queries. This feature allows you to easily implement complex filtering logic with minimal code.

### Supported Operators

The following operators are supported:

| Operator | SQL Equivalent | Description |
|----------|---------------|-------------|
| `null` | `IS NULL` | Matches null values |
| `!null` | `IS NOT NULL` | Matches non-null values |
| `eq` | `=` | Equals (default if no operator specified) |
| `neq` | `!=` | Not equals |
| `empty` | Custom | Matches empty strings or null values |
| `!empty` | Custom | Matches non-empty and non-null values |
| `like` | `LIKE` | Contains (adds wildcards around value) |
| `!like` | `NOT LIKE` | Does not contain (with wildcards) |
| `^like` | `LIKE` | Starts with (adds wildcard after) |
| `like$` | `LIKE` | Ends with (adds wildcard before) |
| `lt` | `<` | Less than |
| `gt` | `>` | Greater than |
| `lte` | `<=` | Less than or equal to |
| `gte` | `>=` | Greater than or equal to |
| `in` | `IN` | In a list of values (comma-separated) |
| `!in` | `NOT IN` | Not in a list of values (comma-separated) |

### Basic Usage

To use advanced operators, extend from the `AdvancedFilter` class instead of `ExtendedFilter`:

```php
<?php namespace App;
 
use CodersCantina\Filter\AdvancedFilter;
 
class ProductFilter extends AdvancedFilter
{
    public function name($value)
    {
        // Automatically handles operator syntax
        $this->applyDynamicFilter('name', $value);
    }
    
    public function price($value)
    {
        // Supports both operators and range syntax
        $this->applyAdvancedRangeFilter('price', $value);
    }
    
    public function created_at($value)
    {
        // Supports both operators and date range syntax
        $this->applyAdvancedDateFilter('created_at', $value);
    }
}
```

### Request Examples

```php
// Find products with names containing "phone"
$filter = new ProductFilter(['name' => 'like:phone']);

// Find products with price greater than or equal to 100
$filter = new ProductFilter(['price' => 'gte:100']);

// Find products created after January 1, 2023
$filter = new ProductFilter(['created_at' => 'gte:2023-01-01']);

// Find products in specific categories
$filter = new ProductFilter(['category' => 'in:electronics,phones,accessories']);

// Find products that are either active or featured
$filter = new ProductFilter(['status' => ['eq:active', 'eq:featured']]);

// Find non-empty descriptions
$filter = new ProductFilter(['description' => '!empty:']);
```

### Advanced Use Cases

#### Using with Range Filters

The advanced filtering still supports the range filter syntax (`...`) while adding operator capabilities:

```php
// Traditional range syntax still works
$filter = new ProductFilter(['price' => '100...500']);

// Using operators for precise comparisons
$filter = new ProductFilter(['price' => 'gte:100']);
```

#### Using with Date Filters

Similarly, date filters can use both traditional range and operator syntax:

```php
// Traditional date range
$filter = new ProductFilter(['created_at' => '2023-01-01...2023-12-31']);

// Using operators
$filter = new ProductFilter(['created_at' => 'gte:2023-01-01']);
```

#### Arrays of Values with Operators

You can combine multiple operator conditions for the same field:

```php
// Products that are either active or pending
$filter = new ProductFilter([
    'status' => ['eq:active', 'eq:pending']
]);
```

#### Custom Operators

You can extend the supported operators by adding your own:

```php
$filter = new ProductFilter(['price' => 'between:10,50']);
$filter->setCustomOperators(['between' => 'BETWEEN']);
```

### Implementation Details

If you're integrating the advanced filter functionality into an existing filter:

1. Use the `AdvancedFilterable` trait in your filter class
2. Extend from `AdvancedFilter` or add the trait to your custom filter class
3. Update your filter methods to use `applyDynamicFilter()`, `applyAdvancedRangeFilter()`, or `applyAdvancedDateFilter()`

```php
<?php namespace App;
 
use CodersCantina\Filter\ExtendedFilter;
use CodersCantina\Filter\AdvancedFilterable;
 
class CustomFilter extends ExtendedFilter
{
    use AdvancedFilterable;
    
    public function status($value)
    {
        $this->applyDynamicFilter('status', $value);
    }
}
```

### Security Considerations

The `AdvancedFilter` class maintains all the security features of the base `Filter` class:

- Column name validation to prevent SQL injection
- Input sanitization for filter values
- Support for filter whitelisting
- Proper handling of null and empty values

Always use filter whitelisting in production to control which filters can be applied from user input:

```php
$filter->setWhitelistedFilters(['name', 'price', 'category', 'status']);
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
