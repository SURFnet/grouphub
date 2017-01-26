<?php

namespace Tests\AppBundle\Ldap;

use AppBundle\Ldap\Normalizer;
use AppBundle\Model\Group;
use PHPUnit_Framework_TestCase;

class NormalizerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $mapping = [
        'group' => [
            'name_prefix' => [
                'ad_hoc' => 'ad_hoc:',
                'semi_formal' => 'semi_formal:',
            ],
            'description' => 'description',
            'extra_attributes' => [],
        ],
    ];

    /**
     * @test
     */
    public function shouldUsePrefixForAdHocGroup()
    {
        $normalizer = new Normalizer($this->mapping);

        $group = new Group(1);
        $group->setName('foo');
        $group->setType(Group::TYPE_GROUPHUB);

        $data = $normalizer->normalizeGroup($group);

        $this->assertSame('ad_hoc:foo_1', $data['cn']);
    }

    /**
     * @test
     */
    public function shouldUsePrefixForFormalGroup()
    {
        $normalizer = new Normalizer($this->mapping);

        $group = new Group(1);
        $group->setName('foo');
        $group->setType(Group::TYPE_FORMAL);

        $data = $normalizer->normalizeGroup($group);

        $this->assertSame('semi_formal:foo_1', $data['cn']);
    }

    /**
     * @test
     */
    public function shouldNotUsePrefixForLdapGroup()
    {
        $normalizer = new Normalizer($this->mapping);

        $group = new Group(1);
        $group->setName('foo');
        $group->setType(Group::TYPE_LDAP);

        $data = $normalizer->normalizeGroup($group);

        $this->assertSame('foo_1', $data['cn']);
    }
}
