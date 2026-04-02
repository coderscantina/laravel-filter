<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Query\Builder;
use Mockery;
use PHPUnit\Framework\TestCase;

class AdvancedFilterableTest extends TestCase
{
    /** @test */
    public function it_handles_advanced_operators()
    {
        $filter = new TestAdvancedFilter(['name' => 'eq:John']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['name', '=', 'John'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_null_operator()
    {
        $filter = new TestAdvancedFilter(['name' => 'null:']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('whereNull')
            ->withArgs(['name'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_not_null_operator()
    {
        $filter = new TestAdvancedFilter(['name' => '!null:']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('whereNotNull')
            ->withArgs(['name'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_empty_operator()
    {
        $filter = new TestAdvancedFilter(['name' => 'empty:']);
        $builder = Mockery::mock(Builder::class);
        $subQueryMock = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->with(Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($callback) use ($subQueryMock, $builder) {
                $callback($subQueryMock);
                return $builder;
            });

        $subQueryMock->shouldReceive('where')
            ->withArgs(['name', ''])
            ->once()
            ->andReturnSelf();

        $subQueryMock->shouldReceive('orWhereNull')
            ->withArgs(['name'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_not_empty_operator()
    {
        $filter = new TestAdvancedFilter(['name' => '!empty:']);
        $builder = Mockery::mock(Builder::class);
        $subQueryMock = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->with(Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($callback) use ($subQueryMock, $builder) {
                $callback($subQueryMock);
                return $builder;
            });

        $subQueryMock->shouldReceive('where')
            ->withArgs(['name', '!=', ''])
            ->once()
            ->andReturnSelf();

        $subQueryMock->shouldReceive('whereNotNull')
            ->withArgs(['name'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_like_operators()
    {
        $filter = new TestAdvancedFilter(['name' => 'like:John']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['name', 'LIKE', '%John%'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_starts_with_like_operator()
    {
        $filter = new TestAdvancedFilter(['name' => '^like:John']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['name', 'LIKE', 'John%'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_ends_with_like_operator()
    {
        $filter = new TestAdvancedFilter(['name' => 'like$:John']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['name', 'LIKE', '%John'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_in_operator()
    {
        $filter = new TestAdvancedFilter(['status' => 'in:active,pending,draft']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('whereIn')
            ->withArgs(['status', ['active', 'pending', 'draft']])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_not_in_operator()
    {
        $filter = new TestAdvancedFilter(['status' => '!in:closed,archived']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('whereNotIn')
            ->withArgs(['status', ['closed', 'archived']])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_comparison_operators()
    {
        $operators = [
            'lt' => '<',
            'gt' => '>',
            'lte' => '<=',
            'gte' => '>=',
            'neq' => '!='
        ];

        foreach ($operators as $operator => $sqlOperator) {
            $filter = new TestAdvancedFilter(['age' => "$operator:30"]);
            $builder = Mockery::mock(Builder::class);

            $builder->shouldReceive('where')
                ->withArgs(['age', $sqlOperator, '30'])
                ->once()
                ->andReturnSelf();

            $filter->apply($builder);
        }

        $this->assertTrue(true);
    }

    /** @test */
    public function it_falls_back_to_equals_for_unknown_operators()
    {
        $filter = new TestAdvancedFilter(['name' => 'unknown:John']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['name', '=', 'unknown:John'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_advanced_date_filters()
    {
        $filter = new TestAdvancedFilter(['created_at' => 'gte:2023-01-01']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['created_at', '>=', '2023-01-01 00:00:00'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_preserves_original_range_filter_functionality()
    {
        $filter = new TestAdvancedFilter(['price' => '10...50']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['price', '>=', '10'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('where')
            ->withArgs(['price', '<=', '50'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_preserves_original_date_range_filter_functionality()
    {
        $filter = new TestAdvancedFilter(['created_at' => '2023-01-01...2023-12-31']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['created_at', '>=', '2023-01-01 00:00:00'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('where')
            ->withArgs(['created_at', '<=', '2023-12-31 23:59:59'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_multiple_advanced_filters()
    {
        $filter = new TestAdvancedFilter([
            'name' => 'like:John',
            'age' => 'gte:30',
            'status' => 'in:active,pending'
        ]);

        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['name', 'LIKE', '%John%'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('where')
            ->withArgs(['age', '>=', '30'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('whereIn')
            ->withArgs(['status', ['active', 'pending']])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_array_of_advanced_filters()
    {
        $filter = new TestAdvancedFilter(['status' => ['eq:active', 'eq:pending']]);
        $builder = Mockery::mock(Builder::class);
        $subQueryMock = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->with(Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($callback) use ($subQueryMock, $builder) {
                $callback($subQueryMock);
                return $builder;
            });

        $subQueryMock->shouldReceive('where')
            ->withArgs(['status', '=', 'active'])
            ->once()
            ->andReturnSelf();

        $subQueryMock->shouldReceive('where')
            ->withArgs(['status', '=', 'pending'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_customize_operators()
    {
        $filter = new TestAdvancedFilter(['age' => 'between:18,30']);
        $filter->setCustomOperators(['between' => 'BETWEEN']);

        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('where')
            ->withArgs(['age', 'BETWEEN', '18,30'])
            ->once()
            ->andReturnSelf();

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}

class TestAdvancedFilter extends AdvancedFilter
{
    public function name($value)
    {
        $this->applyDynamicFilter('name', $value);
    }

    public function age($value)
    {
        $this->applyDynamicFilter('age', $value);
    }

    public function status($value)
    {
        $this->applyDynamicFilter('status', $value);
    }

    public function price($value)
    {
        $this->applyAdvancedRangeFilter('price', $value);
    }

    public function created_at($value)
    {
        $this->applyAdvancedDateFilter('created_at', $value);
    }
}
