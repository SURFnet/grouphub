<?php

namespace Tests\AppBundle\Ldap;

use AppBundle\Ldap\GroupNameFormatter;
use AppBundle\Ldap\Normalizer;
use AppBundle\Ldap\UserMapping;
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
        $userMapping = [
            'firstName' => 'givenName',
            'lastName' => 'sn',
            'email' => 'mail',
            'displayName' => 'displ_name',
            'loginName' => 'an',
            'avatarUrl' => 'image',
            'extraAttribute' => 'extra',
        ];

        $userMapping = new UserMapping($userMapping);

        $normalizer = new Normalizer($this->groupNameFormatter, [], $userMapping);

        $users = [
            'count' => 1,
            0 => [
                'dn' => 'Foo1',
                'givenName' => ['John'],
                'sn' => ['Smith'],
                'an' => ['jsmith'],
                'displ_name' => ['Smith, John'],
                'image' => ['http://example.com/image.jpg'],
                'mail' => ['jsmith@example.com'],
                'extra' => ['Foo Bar'],
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
            'jsmith@example.com',
            'http://example.com/image.jpg',
            ['extraAttribute' => 'Foo Bar']
        );

        $this->assertEquals($expectedUser, $result[0]);
    }
}
