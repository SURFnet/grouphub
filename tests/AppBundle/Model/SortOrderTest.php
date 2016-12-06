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
        SortOrder::createFromSignedName('"foo"');
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromAscendingSignedName()
    {
        $sortOrder = SortOrder::createFromSignedName('name');
        $this->assertEquals('name', $sortOrder->getSignedName());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromDescendingSignedName()
    {
        $sortOrder = SortOrder::createFromSignedName('-name');
        $this->assertEquals('-name', $sortOrder->getSignedName());
    }

    /**
     * @test
     */
    public function shouldCreateAscendingOrder()
    {
        $sortOrder = SortOrder::ascending('name');
        $this->assertEquals('name', $sortOrder->getSignedName());
    }

    /**
     * @test
     */
    public function shouldCreateInstanceFromNameAndOrder()
    {
        $sortOrder = SortOrder::descending('name');
        $this->assertEquals('-name', $sortOrder->getSignedName());
    }
}
