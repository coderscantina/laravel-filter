<?php

namespace CodersCantina\Filter;

use GrahamCampbell\TestBench\AbstractPackageTestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Mockery;

class EloquentTest extends AbstractPackageTestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Mockery::mock(Builder::class);
    }

    /** @test */
    public function itFiltersAColumn()
    {
        $filter = new FooModelFilter(['foo' => 'bar']);
        /** @var Builder $query */
        $query = FooModel::filter($filter);

        $this->assertEquals('select * from "foo_models" where "foo" = ?', $query->toSql());
        $this->assertEquals(['bar'], $query->getBindings());
    }

    /** @test */
    public function itFiltersSomeColumn()
    {
        $filter = new FooModelFilter(['foo' => 'bar', 'baz' => 'quz']);
        /** @var Builder $query */
        $query = FooModel::filter($filter);

        $this->assertEquals('select * from "foo_models" where "foo" = ? and "baz" = ?', $query->toSql());
        $this->assertEquals(['bar', 'quz'], $query->getBindings());
    }

    /** @test */
    public function itSortsSomeColumns()
    {
        $filter = new FooModelFilter(['sort' => '+foo,-bar,baz']);

        $query = FooModel::filter($filter);

        $this->assertEquals(
            'select * from "foo_models" order by "foo_models"."foo" asc, "foo_models"."bar" desc, "foo_models"."baz" asc',
            $query->toSql()
        );
    }

    /** @test */
    public function itSortsARelation()
    {
        $filter = new FooModelFilter(['sort' => '+bar.baz']);
        $query = FooModel::filter($filter);

        $this->assertEquals(
            'select * from "foo_models" left join "bar_models" on "bar_models"."foo_model_id" = "foo_models"."id" order by "bar_models"."baz" asc',
            $query->toSql()
        );

        $filter = new FooModelFilter(['sort' => '+bars.baz']);
        $query = FooModel::filter($filter);

        $this->assertEquals(
            'select * from "foo_models" left join "bar_models" on "foo_models"."bar_id" = "bar_models"."id" order by "bar_models"."baz" asc',
            $query->toSql()
        );
    }

    /** @test */
    public function itReturnsSortColumns()
    {
        $filter = new FooModelFilter(['sort' => '+bar.baz']);
        $query = FooModel::filter($filter);

        $this->assertEquals(['bar_models.baz'], $filter->getSortColumns());
    }

    /** @test */
    public function itReturnsSortColumnsForNonExistingRelations()
    {
        $filter = new FooModelFilter(['sort' => '+quz.baz']);
        $query = FooModel::filter($filter);

        $this->assertEquals(['quz.baz'], $filter->getSortColumns());
    }

    /** @test */
    public function ifFiltersARange()
    {
        $filter = new FooModelFilter(['bar' => 'a...b']);
        $query = FooModel::filter($filter);

        $this->assertEquals('select * from "foo_models" where "bar" >= ? and "bar" <= ?', $query->toSql());
        $this->assertEquals(['a', 'b'], $query->getBindings());
    }

    /** @test */
    public function itFiltersADateRange()
    {
        $filter = new FooModelFilter(['quz' => '2017-01-01 12:34:56...2017-12-31']);
        $query = FooModel::filter($filter);

        $this->assertEquals('select * from "foo_models" where "quz" >= ? and "quz" <= ?', $query->toSql());
        $this->assertEquals(['2017-01-01 12:34:56', '2017-12-31 23:59:59'], $query->getBindings());
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

}

class FooModelFilter extends ExtendedFilter
{
    use Sortable;

    public function foo($value)
    {
        $this->builder->where('foo', $value);
    }

    public function baz($value)
    {
        $this->builder->where('baz', $value);
    }

    public function quz($value = null)
    {
        $this->applyDateFilter('quz', $value);
    }

    public function bar($value = null)
    {
        $this->applyRangeFilter('bar', $value);
    }
}

class FooModel extends Model
{
    use Filterable;

    public function bar()
    {
        return $this->hasMany(BarModel::class);
    }

    public function bars()
    {
        return $this->belongsTo(BarModel::class, 'bar_id', 'id');
    }
}

class BarModel extends Model
{

}
