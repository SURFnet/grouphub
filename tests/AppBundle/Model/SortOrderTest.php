<?php

namespace Tests\AppBundle\Model;

use AppBundle\Model\SortOrder;
use \InvalidArgumentException;

class SortOrderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldGuardAgainstInvalidName()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        new SortOrder('"foo"');
    }

    /**
     * @test
     */
    public function shouldGuardAgainstInvalidDirection()
    {
        $this->setExpectedException(InvalidArgumentException::class);
        new SortOrder('foo', 'bar');
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromAscendingSignedName()
    {
        $sortOrder = SortOrder::createFromSignedName('name');
        $this->assertEquals('name', $sortOrder->getColumn());
        $this->assertEquals(0, $sortOrder->getDirection());
        $this->assertEquals('name', $sortOrder->getSignedName());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromDescendingSignedName()
    {
        $sortOrder = SortOrder::createFromSignedName('-name');
        $this->assertEquals('name', $sortOrder->getColumn());
        $this->assertEquals(1, $sortOrder->getDirection());
        $this->assertEquals('-name', $sortOrder->getSignedName());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromNameWithDefaultOrder()
    {
        $sortOrder = new SortOrder('name');
        $this->assertEquals('name', $sortOrder->getColumn());
        $this->assertEquals(0, $sortOrder->getDirection());
        $this->assertEquals('name', $sortOrder->getSignedName());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromNameAndOrder()
    {
        $sortOrder = new SortOrder('name', 1);
        $this->assertEquals('name', $sortOrder->getColumn());
        $this->assertEquals(1, $sortOrder->getDirection());
        $this->assertEquals('-name', $sortOrder->getSignedName());
    }
}
