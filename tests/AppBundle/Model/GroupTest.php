<?php

namespace Tests\AppBundle\Model;


use AppBundle\Model\Group;
use PHPUnit_Framework_TestCase;

class GroupTest extends PHPUnit_Framework_TestCase
{
    private $defaultMapping = [
        'name' => 'name',
        'description' => 'description'
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
}
