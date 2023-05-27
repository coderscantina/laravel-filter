# Filter Package from Coders' Cantina

A filter object for Laravel/Eloquent models based on laracasts approach.

## Features


## Getting started

* Install this package

## Install

Require this package with composer:

``` bash
$ composer require coderscantina/filter
```

## Usage

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

        return TestModel::filter($filter)->get();
    }
}
```

### Sortable

The `Sortable` trait which is included in the `ExtendedFilter` offers sorting abilities:

```php
['sort' => '+foo,-bar']; // -> order by foo asc, bar desc
```

It is also possible to sort using a foreign key relation:

```php
['sort' => '+foo.bar']; // -> left join x on x.id = foo.id order by foo.bar asc
```

To limit the sortable columns, override the `sortColumns` field:

```php
   protected $sortColumns = ['foo', 'bar'];
```

### Range Filter

The `ExtendedFilter` offers helper for range filter, in the form:

```php
['foo' => 'abc...']; // -> foo >= 'abc'
['foo' => '...efg']; // -> foo <= 'abc'
['foo' => 'abc...efg']; // -> foo >= 'abc' and foo <= 'abc'
```

And helpers for date range filtering:

```php
['foo' => '2017-01-01...']; // -> foo >= '2017-01-01 00:00:00'
['foo' => '...2017-12-31 01:02:03']; // -> foo <= '2017-12-31 01:02:03'
['foo' => '2017-01-01...2017-12-31']; // -> foo >= '2017-01-01 00:00:00' and foo <= '2017-12-31 23:59:59' 
```

## Extending hints

* Override the method `getSortColumns` to have a custom implementation which columns are searchable.
* Override the method `isValidMethod` to further limit the possible query params. Keep in mind to return `sort` in the array to allow sorting.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```


