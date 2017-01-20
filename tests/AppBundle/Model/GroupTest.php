<?php

namespace Tests\AppBundle\Model;

use AppBundle\Model\Group;
use InvalidArgumentException;
use PHPUnit_Framework_TestCase;

class GroupTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $defaultMapping = [
        'name' => 'name',
        'description' => 'description',
    ];

    /**
     * @test
     */
    public function shouldNotBeConsideredEqualToGroupWithDifferentCn()
    {
        $fooGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $barGroup = new Group(1, 'cn=bar,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $this->assertFalse($fooGroup->equals($barGroup, $this->defaultMapping));
    }

    /**
     * @test
     */
    public function shouldNotBeConsideredEqualToGroupWithDifferentName()
    {
        $fooGroup = new Group(1, 'cn=bar,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $barGroup = new Group(1, 'cn=bar,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'OtherName', 'Description');
        $this->assertFalse($fooGroup->equals($barGroup, $this->defaultMapping));
    }

    /**
     * @test
     */
    public function shouldNotBeConsideredEqualToGroupWithDifferentDescription()
    {
        $fooGroup = new Group(1, 'cn=bar,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $barGroup = new Group(1, 'cn=bar,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'OtherDescription');
        $this->assertFalse($fooGroup->equals($barGroup, $this->defaultMapping));
    }

    /**
     * @test
     */
    public function shouldBeConsideredEqualToGroupWithDifferentNameWhenNameIsNotMapped()
    {
        $fooGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $barGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'OtherName', 'Description');
        $this->assertTrue($fooGroup->equals($barGroup, array_merge($this->defaultMapping, ['name' => null])));
    }

    /**
     * @test
     */
    public function shouldBeConsideredEqualToGroupWithDifferentDescriptionWhenDescriptionIsNotMapped()
    {
        $fooGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $barGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'OtherDescription');
        $this->assertTrue($fooGroup->equals($barGroup, array_merge($this->defaultMapping, ['description' => null])));
    }

    /**
     * @test
     */
    public function shouldBeConsideredEqualToGroup()
    {
        $fooGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $barGroup = new Group(1, 'cn=foo,ou=AdHoc,ou=Grouphub,dc=surfuni,dc=org', 'Name', 'Description');
        $this->assertTrue($fooGroup->equals($barGroup, $this->defaultMapping));
    }

    /**
     * @test
     */
    public function shouldFailIfGivenTypeDoesNotExist()
    {
        $group = new Group(1);

        $this->setExpectedException(InvalidArgumentException::class);

        $group->isOfType('NonExistingType');
    }

    /**
     * @test
     */
    public function shouldReturnTrueIfGivenTypeMatchesType()
    {
        $group = new Group(1);
        $group->setType(Group::TYPE_LDAP);

        $this->assertTrue($group->isOfType(Group::TYPE_LDAP));
    }

    /**
     * @test
     */
    public function shouldReturnFalseIfGivenTypeDoesNotMatchType()
    {
        $group = new Group(1);
        $group->setType(Group::TYPE_FORMAL);

        $this->assertFalse($group->isOfType(Group::TYPE_GROUPHUB));
    }
}
