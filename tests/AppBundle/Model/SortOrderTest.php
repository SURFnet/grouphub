<?php

namespace Tests\AppBundle\Model;

use AppBundle\Model\SortOrder;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class SortOrderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldGuardAgainstInvalidName()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        SortOrder::createFromSignedOrder('"foo"');
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromAscendingSignedOrder()
    {
        $sortOrder = SortOrder::createFromSignedOrder('name');
        $this->assertEquals('name', $sortOrder->toSignedOrder());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromDescendingSignedOrder()
    {
        $sortOrder = SortOrder::createFromSignedOrder('-name');
        $this->assertEquals('-name', $sortOrder->toSignedOrder());
    }

    /**
     * @test
     */
    public function shouldCreateAscendingOrder()
    {
        $sortOrder = SortOrder::ascending('name');
        $this->assertEquals('name', $sortOrder->toSignedOrder());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromNameAndOrder()
    {
        $sortOrder = SortOrder::descending('name');
        $this->assertEquals('-name', $sortOrder->toSignedOrder());
    }
}
