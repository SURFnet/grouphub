<?php

namespace Tests\AppBundle\Model;

use AppBundle\Model\User;
use PHPUnit_Framework_TestCase;

class UserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldReturnFullName()
    {
        $user = new User(123, '', 'John', 'Smith', 'John1', 'jsmith', []);

        $this->assertSame('John Smith', $user->getName());
    }

    /**
     * @test
     */
    public function shouldReturnDisplayName()
    {
        $user = new User(123, '', 'John', 'Smith', 'John1', 'jsmith', []);

        $this->assertSame('John1', $user->getDisplayName());
    }
}
