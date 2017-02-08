<?php

namespace Tests\AppBundle\Ldap;

use AppBundle\Ldap\MappingFactory;
use AppBundle\Ldap\UserMapping;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class MappingFactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldFailIfUserMappingIsMissing()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        new MappingFactory([]);
    }

    /**
     * @test
     */
    public function shouldFailIfUserMappingIsNotAnArray()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        new MappingFactory(['user' => 1]);
    }

    /**
     * @test
     */
    public function shouldReturnUserMapping()
    {
        $factory = new MappingFactory(['user' => []]);

        $this->assertInstanceOf(UserMapping::class, $factory->getUserMapping());
    }
}
