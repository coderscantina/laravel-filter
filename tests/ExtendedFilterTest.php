<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Query\Builder;
use Mockery;

class ExtendedFilterTest extends MockeryTestCase
{
    /** @test */
    public function itSortsAscendingPerDefault()
    {
        $filter = new BarFilter(['sort' => 'foo']);
        $modelMock = $this->mockModel();
        $this->shouldReceive($modelMock);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('orderBy')->withArgs(['tickets.foo', 'asc'])->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itIgnoresAnEmptySearch()
    {
        $filter = new BarFilter(['sort' => null]);
        $builder = Mockery::mock(Builder::class);
        $modelMock = $this->mockModel();
        $this->shouldReceive($modelMock);

        $builder->shouldNotReceive('orderBy');
        $builder->shouldReceive('orderBy');

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itSortsDescending()
    {
        $filter = new BarFilter(['sort' => '-foo']);
        $builder = Mockery::mock(Builder::class);
        $modelMock = $this->mockModel();
        $this->shouldReceive($modelMock);

        $builder->shouldReceive('orderBy')->withArgs(['tickets.foo', 'desc'])->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itSortsAscending()
    {
        $filter = new BarFilter(['sort' => '+foo']);
        $builder = Mockery::mock(Builder::class);
        $modelMock = $this->mockModel();
        $this->shouldReceive($modelMock);

        $builder->shouldReceive('orderBy')->withArgs(['tickets.foo', 'asc'])->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itSortsMultipleColumns()
    {
        $filter = new BarFilter(['sort' => '+foo,-bar,+baz']);
        $builder = Mockery::mock(Builder::class);

        $modelMock = $this->mockModel();
        $this->shouldReceive($modelMock);

        $builder->shouldReceive('orderBy')->withArgs(['tickets.foo', 'asc'])->andReturnSelf();
        $builder->shouldReceive('orderBy')->withArgs(['tickets.bar', 'desc'])->andReturnSelf();
        $builder->shouldReceive('orderBy')->withArgs(['tickets.baz', 'asc'])->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itWhitelistsTheSort()
    {
        $filter = new BarSortFilter(['sort' => '+foo,-bar,+baz']);
        $builder = Mockery::mock(Builder::class);

        $modelMock = $this->mockModel();
        $this->shouldReceive($modelMock);

        $builder->shouldReceive('orderBy')->withArgs(['tickets.foo', 'asc'])->andReturnSelf();
        $builder->shouldReceive('orderBy')->withArgs(['tickets.bar', 'desc'])->andReturnSelf();
        $builder->shouldReceive('orderBy')->withArgs(['tickets.baz', 'asc'])->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itFiltersARange()
    {
        $filter = new BarFilter(['foo' => 'bar...baz']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')->withArgs(['foo', '>=', 'bar'])->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(['foo', '<=', 'baz'])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itFiltersABeginningRange()
    {
        $filter = new BarFilter(['foo' => 'bar...']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')->withArgs(['foo', '>=', 'bar'])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itFiltersAnEndingRange()
    {
        $filter = new BarFilter(['foo' => '...bar']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')->withArgs(['foo', '<=', 'bar'])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itFiltersADateRange()
    {
        $filter = new BarFilter(['bar' => '2017-01-01...2017-12-31']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')->withArgs(['bar', '>=', '2017-01-01 00:00:00'])->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(['bar', '<=', '2017-12-31 23:59:59'])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itFiltersADatetimeRange()
    {
        $filter = new BarFilter(['bar' => '2017-01-01 01:02:03...2017-12-31 04:05:06']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')->withArgs(['bar', '>=', '2017-01-01 01:02:03'])->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(['bar', '<=', '2017-12-31 04:05:06'])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itFilterARangeWithACustomTransformer()
    {
        $filter = new BarFilter(['baz' => 'abc...def']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')->withArgs(['bar', '>=', 'ABC'])->andReturnSelf();
        $builder->shouldReceive('where')->withArgs(['bar', '<=', 'DEF'])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itAppliesALimit()
    {
        $filter = new BarFilter(['limit' => '10']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('limit')->withArgs([10])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itAppliesAnOffset()
    {
        $filter = new BarFilter(['offset' => '10']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('offset')->withArgs([10])->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function itEnsuresAnArray()
    {
        $filter = new BarFilter([]);

        $this->assertEquals(['foo'], $filter->arrayFoo('foo'));
        $this->assertEquals(['foo', 'bar'], $filter->arrayFoo('foo,bar'));
        $this->assertEquals(['foo'], $filter->arrayFoo(['foo']));
        $this->assertEquals(['foo', 'bar'], $filter->arrayFoo(['foo', 'bar']));
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}

class BarFilter extends ExtendedFilter
{
    use Sortable;

    public function bar($value = null)
    {
        $this->applyDateFilter('bar', $value);
    }

    public function baz($value = null)
    {
        $this->applyRangeFilter('bar', $value, function ($v) {
            return strtoupper($v);
        });
    }

    public function foo($value = null)
    {
        $this->applyRangeFilter('foo', $value);
    }

    public function arrayFoo($value)
    {
        return $this->ensureArray($value);
    }

}

class BarSortFilter extends BarFilter
{
    protected array $sortableColumns = ['foo', 'baz'];
}
