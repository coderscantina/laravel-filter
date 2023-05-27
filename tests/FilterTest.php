<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Builder;
use Mockery;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{

    /** @test */
    public function itFiltersOneArgument()
    {
        $filter = new FooFilter(['foo' => 'bar']);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->withArgs(['foo', 'bar'])->andReturnSelf();

        $this->assertEquals($builder, $filter->apply($builder));
    }

    /** @test */
    public function itIgnoresNonPresentFilters()
    {
        $filter = new FooFilter(['foo' => 'bar', 'baz' => 'qux']);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->withArgs(['foo', 'bar'])->andReturnSelf();
        $builder->shouldNotReceive('where')->withArgs(['baz', 'qux']);

        $this->assertEquals($builder, $filter->apply($builder));
    }

    /** @test */
    public function itIgnoresNonPublicMethods()
    {
        $filter = new FooFilter(['foo' => 'bar', 'zfoo' => 'qux']);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->withArgs(['foo', 'bar'])->andReturnSelf();
        $builder->shouldNotReceive('where')->withArgs(['zfoo', 'qux']);

        $this->assertEquals($builder, $filter->apply($builder));
    }

    /** @test */
    public function itCallsWithAnNulledValue()
    {
        $filter = new FooFilter(['foo' => null]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->withArgs(['foo', null])->andReturnSelf();

        $this->assertEquals($builder, $filter->apply($builder));
    }

    /** @test */
    public function itAllowsFilterWithoutParam()
    {
        $filter = new FooFilter(['active' => null, 'inactive' => null, 'default' => null]);

        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('where')->once()->withArgs(['state', 'active'])->andReturnSelf();
        $builder->shouldReceive('where')->once()->withArgs(['state', 'inactive'])->andReturnSelf();
        $builder->shouldReceive('where')->once()->withArgs(['default', 1])->andReturnSelf();

        $this->assertEquals($builder, $filter->apply($builder));
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}

class FooFilter extends Filter
{
    public function foo($value = null)
    {
        $this->builder->where('foo', $value);
    }

    public function active()
    {
        $this->builder->where('state', 'active');
    }

    public function default($value = 1)
    {
        $this->builder->where('default', $value);
    }

    public function inactive()
    {
        $this->builder->where('state', 'inactive');
    }

    protected function zfoo($value = null)
    {
        $this->builder->where('foo', $value);
    }
}
