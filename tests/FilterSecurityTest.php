<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\TestCase;

class FilterSecurityTest extends TestCase
{
    /** @test */
    public function it_sanitizes_input_data()
    {
        $filter = new TestFilter([
            'valid_key' => 'valid_value',
            'sql_injection; DROP TABLE users;' => 'malicious_value',
            '<script>alert("xss")</script>' => 'xss_attempt',
            'nested' => [
                'valid' => 'ok',
                'invalid;' => 'not ok'
            ]
        ]);

        $filters = $filter->getFilters();

        // Check that invalid keys are removed
        $this->assertArrayHasKey('valid_key', $filters);
        $this->assertArrayHasKey('nested', $filters);
        $this->assertArrayNotHasKey('sql_injection; DROP TABLE users;', $filters);
        $this->assertArrayNotHasKey('<script>alert("xss")</script>', $filters);

        // Check that nested array is also sanitized
        $this->assertArrayHasKey('valid', $filters['nested']);
        $this->assertArrayNotHasKey('invalid;', $filters['nested']);
    }

    /** @test */
    public function it_validates_column_names()
    {
        $filter = new TestFilter([]);

        // Valid column names
        $this->assertTrue($filter->testIsValidColumnName('column_name'));
        $this->assertTrue($filter->testIsValidColumnName('column123'));
        $this->assertTrue($filter->testIsValidColumnName('table_name.column_name'));

        // Invalid column names (SQL injection attempts)
        $this->assertFalse($filter->testIsValidColumnName('column; DROP TABLE users;'));
        $this->assertFalse($filter->testIsValidColumnName('column" OR 1=1--'));
        $this->assertFalse($filter->testIsValidColumnName("column' OR '1'='1"));
    }

    /** @test */
    public function it_validates_date_formats()
    {
        $filter = new TestFilter([]);

        // Valid date formats
        $this->assertTrue($filter->testIsValidDateFormat('2023-01-01'));
        $this->assertTrue($filter->testIsValidDateFormat('2023-01-01 12:34:56'));

        // Invalid date formats
        $this->assertFalse($filter->testIsValidDateFormat('01-01-2023'));
        $this->assertFalse($filter->testIsValidDateFormat('2023/01/01'));
        $this->assertFalse($filter->testIsValidDateFormat('2023-01-01; DROP TABLE users;'));
        $this->assertFalse($filter->testIsValidDateFormat('<script>alert("xss")</script>'));
    }

    /** @test */
    public function it_rejects_invalid_column_names_in_range_filter()
    {
        $filter = new TestFilter(['sql_injection' => 'value']);
        $builder = Mockery::mock(Builder::class);

        // Builder should not receive any where calls for invalid column names
        $builder->shouldNotReceive('where');

        $filter->testApplyRangeFilter('column; DROP TABLE users;', 'a...b');

        $this->assertTrue(true); // No exception thrown
    }

    /** @test */
    public function it_handles_invalid_date_format_gracefully()
    {
        $filter = new TestFilter(['date_filter' => 'invalid_date']);
        $builder = Mockery::mock(Builder::class);

        $this->expectException(InvalidArgumentException::class);

        $filter->testFormatDate('invalid-date-format', true);
    }

    /** @test */
    public function it_rejects_malicious_sort_strings()
    {
        $filter = new SortableTestFilter(['sort' => '+valid_column,-another_column,malicious;DROP TABLE users;']);
        $builder = Mockery::mock(Builder::class);
        $modelMock = Mockery::mock(Model::class);

        $modelMock->shouldReceive('getTable')->andReturn('test_table');
        $modelMock->shouldReceive('newInstance')->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        // The first two valid columns should receive orderBy
        $builder->shouldReceive('orderBy')
            ->withArgs(['test_table.valid_column', 'asc'])
            ->andReturnSelf();

        $builder->shouldReceive('orderBy')
            ->withArgs(['test_table.another_column', 'desc'])
            ->andReturnSelf();

        // The malicious column should not be ordered
        $builder->shouldNotReceive('orderBy')
            ->withArgs(['test_table.malicious;DROP TABLE users;', Mockery::any()]);

        $filter->apply($builder);

        // Only valid columns should be in the sort columns array
        $sortColumns = $filter->getSortColumns();
        $this->assertCount(2, $sortColumns);
        $this->assertContains('test_table.valid_column', $sortColumns);
        $this->assertContains('test_table.another_column', $sortColumns);
    }


    /** @test */
    public function it_creates_filter_from_request()
    {
        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('all')->andReturn([
            'filter1' => 'value1',
            'filter2' => 'value2'
        ]);

        $filter = TestFilter::fromRequest($requestMock);

        $this->assertInstanceOf(TestFilter::class, $filter);
        $this->assertEquals([
            'filter1' => 'value1',
            'filter2' => 'value2'
        ], $filter->getFilters());
    }


    /** @test */
    public function it_validates_limit_input()
    {
        $filter = new TestFilter(['limit' => '10']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('limit')->withArgs([10])->andReturnSelf();
        $filter->apply($builder);

        // Test with negative value (should not be applied)
        $filter = new TestFilter(['limit' => '-10']);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldNotReceive('limit');
        $filter->apply($builder);

        // Test with non-numeric value (should not be applied)
        $filter = new TestFilter(['limit' => 'abc']);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldNotReceive('limit');
        $filter->apply($builder);
    }

    /** @test */
    public function it_validates_offset_input()
    {
        $filter = new TestFilter(['offset' => '10']);
        $builder = Mockery::mock(Builder::class);

        $builder->shouldReceive('offset')->withArgs([10])->andReturnSelf();
        $filter->apply($builder);

        // Test with negative value (should not be applied)
        $filter = new TestFilter(['offset' => '-10']);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldNotReceive('offset');
        $filter->apply($builder);

        // Test with non-numeric value (should not be applied)
        $filter = new TestFilter(['offset' => 'abc']);
        $builder = Mockery::mock(Builder::class);
        $builder->shouldNotReceive('offset');
        $filter->apply($builder);
    }

    /** @test */
    public function it_safely_handles_array_conversion()
    {
        $filter = new TestFilter([]);

        // Test with comma-separated string
        $result = $filter->testEnsureArray('one,two,three');
        $this->assertEquals(['one', 'two', 'three'], $result);

        // Test with already-array input
        $result = $filter->testEnsureArray(['one', 'two', 'three']);
        $this->assertEquals(['one', 'two', 'three'], $result);

        // Test with malicious input
        $result = $filter->testEnsureArray('one,two",DELETE FROM users,"three');
        $this->assertEquals(['one', 'two"', 'DELETE FROM users', '"three'], $result);

        // Test with non-string, non-array
        $result = $filter->testEnsureArray(123);
        $this->assertEquals([], $result);
    }

    /** @test */
    public function it_limits_maximum_sort_columns()
    {
        $filter = new SortableTestFilter(['sort' => 'col1,col2,col3,col4,col5,col6,col7,col8']);
        $filter->setMaxSortColumns(3);

        $builder = Mockery::mock(Builder::class);
        $modelMock = Mockery::mock(Model::class);

        $modelMock->shouldReceive('getTable')->andReturn('test_table');
        $modelMock->shouldReceive('newInstance')->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        // Only the first 3 columns should be ordered
        $builder->shouldReceive('orderBy')
            ->withArgs(['test_table.col1', 'asc'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('orderBy')
            ->withArgs(['test_table.col2', 'asc'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('orderBy')
            ->withArgs(['test_table.col3', 'asc'])
            ->once()
            ->andReturnSelf();

        // The remaining columns should not be ordered
        $builder->shouldNotReceive('orderBy')
            ->withArgs(['test_table.col4', Mockery::any()]);

        $filter->apply($builder);

        // Only the first 3 columns should be in sort columns
        $this->assertCount(3, $filter->getSortColumns());
    }

    /** @test */
    public function it_handles_join_caching_for_relations()
    {
        $filter = new RelationTestFilter(['sort' => 'related.column']);

        $builder = Mockery::mock(Builder::class);
        $modelMock = Mockery::mock(Model::class);
        $relationMock = Mockery::mock(Relation::class);
        $relatedModelMock = Mockery::mock(Model::class);

        $modelMock->shouldReceive('getTable')->andReturn('test_table');
        $modelMock->shouldReceive('newInstance')->andReturnSelf();

        // Setup relation
        $modelMock->shouldReceive('related')->andReturn('relation_method');
        $builder->shouldReceive('getModel')->andReturn($modelMock);
        $builder->shouldReceive('getRelation')->with('related')->andReturn($relationMock);

        $relationMock->shouldReceive('getRelated')->andReturn($relatedModelMock);
        $relatedModelMock->shouldReceive('getTable')->andReturn('related_table');

        // Relation methods
        $relationMock->shouldReceive('getQualifiedForeignKeyName')->andReturn('related_table.foreign_key');
        $relationMock->shouldReceive('getQualifiedParentKeyName')->andReturn('test_table.id');

        // BelongsTo check
        $relationMock->shouldReceive('instanceof')->with(Mockery::any())->andReturn(false);

        // The join should only be called once
        $builder->shouldReceive('leftJoin')
            ->with(
                'related_table',
                'related_table.foreign_key',
                '=',
                'test_table.id'
            )
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('orderBy')
            ->withArgs(['related_table.column', 'asc'])
            ->andReturnSelf();

        // Apply sort twice to test caching
        $filter->apply($builder);
        $filter->sort('related.column'); // Second application should use cached join
    }

    /** @test */
    public function it_sanitizes_request_data()
    {
        $requestMock = Mockery::mock(Request::class);
        $requestMock->shouldReceive('all')->andReturn([
            'valid_filter' => 'value1',
            'malicious; DROP TABLE users;' => 'value2'
        ]);

        $filter = TestFilter::fromRequest($requestMock);
        $filters = $filter->getFilters();

        $this->assertArrayHasKey('valid_filter', $filters);
        $this->assertArrayNotHasKey('malicious; DROP TABLE users;', $filters);
    }

    /** @test */
    public function it_respects_whitelisted_filters()
    {
        $filter = new TestFilter([
            'allowed_filter' => 'value1',
            'another_allowed' => 'value2',
            'forbidden_filter' => 'value3'
        ]);

        $filter->setWhitelistedFilters(['allowed_filter', 'another_allowed']);

        $builder = Mockery::mock(Builder::class);

        // Only the whitelisted methods should be called
        $builder->shouldReceive('where')
            ->withArgs(['allowed_filter', 'value1'])
            ->once()
            ->andReturnSelf();

        $builder->shouldReceive('where')
            ->withArgs(['another_allowed', 'value2'])
            ->once()
            ->andReturnSelf();

        $builder->shouldNotReceive('where')
            ->withArgs(['forbidden_filter', 'value3']);

        $filter->apply($builder);
    }

    /** @test */
    public function it_handles_invalid_relation_gracefully()
    {
        $filter = new SortableTestFilter(['sort' => 'invalid_relation.column']);

        $builder = Mockery::mock(Builder::class);
        $modelMock = Mockery::mock(Model::class);

        $modelMock->shouldReceive('getTable')->andReturn('test_table');
        $modelMock->shouldReceive('newInstance')->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        // Setup to throw an exception when getting relation
        $modelMock->shouldReceive('invalid_relation')->andThrow(new \Exception('Relation not found'));
        $builder->shouldReceive('getRelation')->with('invalid_relation')->andThrow(new \Exception('Relation not found'));

        // Should fall back to default behavior
        $builder->shouldReceive('orderBy')
            ->withArgs(['invalid_relation.column', 'asc'])
            ->andReturnSelf();

        // This should not throw an exception
        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_handles_invalid_path_segments_gracefully()
    {
        $filter = new SortableTestFilter(['sort' => '+invalid;DROP TABLE;.column']);

        $builder = Mockery::mock(Builder::class);
        $modelMock = Mockery::mock(Model::class);

        $modelMock->shouldReceive('getTable')->andReturn('test_table');
        $modelMock->shouldReceive('newInstance')->andReturnSelf();
        $builder->shouldReceive('getModel')->andReturn($modelMock);

        // No orderBy should be called due to invalid path
        $builder->shouldNotReceive('orderBy');

        $filter->apply($builder);
        $this->assertTrue(true);
    }

    /** @test */
    public function it_formats_date_with_fallback_on_exception()
    {
        $filter = new class([]) extends TestFilter {
            public function testFormatDateWithException(string $date, bool $isStart): string
            {
                // Override isValidDateFormat to simulate validation passing
                $this->isValidDateFormatResult = true;

                // But make Carbon::parse throw an exception
                return parent::formatDate('not a real date but passes validation', $isStart);
            }

            protected function isValidDateFormat(string $date): bool
            {
                return $this->isValidDateFormatResult ?? parent::isValidDateFormat($date);
            }

            private $isValidDateFormatResult;
        };

        // Should return fallback dates
        $startDate = $filter->testFormatDateWithException('invalid but passes validation', true);
        $endDate = $filter->testFormatDateWithException('invalid but passes validation', false);

        $this->assertEquals('1970-01-01 00:00:00', $startDate);
        $this->assertEquals('2099-12-31 23:59:59', $endDate);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }
}


// Helper test classes
class TestFilter extends ExtendedFilter
{
    public function allowed_filter($value)
    {
        $this->builder->where('allowed_filter', $value);
    }

    public function another_allowed($value)
    {
        $this->builder->where('another_allowed', $value);
    }

    public function forbidden_filter($value)
    {
        $this->builder->where('forbidden_filter', $value);
    }

    public function name($value)
    {
        $this->builder->where('name', $value);
    }

    // Test helper methods to expose protected methods for testing
    public function testIsValidColumnName(string $column): bool
    {
        return $this->isValidColumnName($column);
    }

    public function testIsValidDateFormat(string $date): bool
    {
        return $this->isValidDateFormat($date);
    }

    public function testApplyRangeFilter(string $column, string $value, callable $cb = null): void
    {
        $this->applyRangeFilter($column, $value, $cb);
    }

    public function testFormatDate(string $date, bool $isStart): string
    {
        return $this->formatDate($date, $isStart);
    }

    public function testEnsureArray($ids): array
    {
        return $this->ensureArray($ids);
    }
}


class SortableTestFilter extends TestFilter
{
    use Sortable;
}

class RelationTestFilter extends TestFilter
{
    use Sortable;
}
