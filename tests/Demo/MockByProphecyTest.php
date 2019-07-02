<?php

namespace Test\Demo;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophet;

/**
 * 使用 Prophecy 创建 Mock
 * Class MockByProphecyTest
 * @package Test\Demo
 */
class MockByProphecyTest extends TestCase
{
    /**
     * @var Prophet
     */
    private $prophet;

    public function setUp()
    {
        $this->prophet = new Prophet();
    }

    public function tearDown()
    {
        $this->prophet->checkPredictions();
    }
}
