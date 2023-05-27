<?php

namespace CodersCantina\Filter;

use Illuminate\Database\Eloquent\Model;
use Mockery;
use PHPUnit\Framework\TestCase;

class MockeryTestCase extends TestCase
{
    public function mockModel() {
        return Mockery::mock(Model::class);
    }

    public function shouldReceive($modelMock) {
        $modelMock->shouldReceive('newInstance')->andReturnSelf();
        $modelMock->shouldReceive('getTable')->andReturn('tickets');
    }
}
