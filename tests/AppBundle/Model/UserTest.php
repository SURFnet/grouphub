<?php

namespace Tests\AppBundle\Model;

use AppBundle\Model\User;
use PHPUnit_Framework_TestCase;

class UserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldSetEmailAddressFromSamlAttributes()
    {
        $user = new User();

        $user->setSamlAttributes(['urn:mace:dir:attribute-def:mail' => ['foo@example.com']]);

        $this->assertSame('foo@example.com', $user->getEmailAddress());
    }

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

    /**
     * @test
     */
    public function shouldReturnEmailAddress()
    {
        $user = new User(123, '', 'John', 'Smith', 'John1', 'jsmith', 'jsmith@example.com', []);

        $this->assertSame('jsmith@example.com', $user->getEmailAddress());
    }

    /**
     * @test
     */
    public function shouldReturnAvatarUrl()
    {
        $user = new User(
            123,
            '',
            'John',
            'Smith',
            'John1',
            'jsmith',
            'jsmith@example.com',
            'http://example.com/image.jpg'
        );

        $this->assertSame('http://example.com/image.jpg', $user->getAvatarUrl());
    }
}
