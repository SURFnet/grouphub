<?php

namespace Tests\AppBundle\Ldap;

use AppBundle\Ldap\UserMapping;
use PHPUnit_Framework_TestCase;

class UserMappingTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldReturnTrueIfFieldIsMapped()
    {
        $userMapping = new UserMapping(['field' => 'attribute']);

        $this->assertTrue($userMapping->hasField('field'));
    }

    /**
     * @test
     */
    public function shouldReturnFalseIfFieldIsNotMapped()
    {
        $userMapping = new UserMapping(['field' => 'attribute']);

        $this->assertFalse($userMapping->hasField('otherField'));
    }

    /**
     * @test
     */
    public function shouldReturnLdapAttributeNameIfItIsMapped()
    {
        $userMapping = new UserMapping(['field' => 'attribute']);

        $this->assertSame('attribute', $userMapping->getLdapAttributeName('field'));
    }
}
