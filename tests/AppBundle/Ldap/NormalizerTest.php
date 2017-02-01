<?php

namespace Tests\AppBundle\Ldap;

use AppBundle\Ldap\GroupNameFormatter;
use AppBundle\Ldap\Normalizer;
use AppBundle\Model\User;
use PHPUnit_Framework_TestCase;

class NormalizerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var GroupNameFormatter
     */
    private $groupNameFormatter;

    protected function setUp()
    {
        $this->groupNameFormatter = new GroupNameFormatter('', '');
    }

    /**
     * @test
     */
    public function shouldDenormalizeUsers()
    {
        $mapping = [
            'user' => [
                'firstName' => 'givenName',
                'lastName' => 'sn',
                'email' => 'mail',
                'displayName' => 'displ_name',
                'loginName' => 'an',
            ],
        ];

        $normalizer = new Normalizer($this->groupNameFormatter, $mapping);

        $users = [
            'count' => 1,
            0 => [
                'dn' => 'Foo1',
                'givenName' => ['John'],
                'sn' => ['Smith'],
                'an' => ['jsmith'],
                'displ_name' => ['Smith, John'],
                'mail' => ['jsmith@example.com'],
            ],
        ];

        $result = $normalizer->denormalizeUsers($users);

        $this->assertCount(1, $result);

        $expectedUser = new User(
            null,
            'Foo1',
            'John',
            'Smith',
            'Smith, John',
            'jsmith',
            ['email' => 'jsmith@example.com']
        );

        $this->assertEquals($expectedUser, $result[0]);
    }
}
