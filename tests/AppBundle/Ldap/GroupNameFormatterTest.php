<?php

namespace Tests\AppBundle\Ldap;

use AppBundle\Ldap\GroupNameFormatter;
use AppBundle\Model\Group;
use PHPUnit_Framework_TestCase;

class GroupNameFormatterTest extends PHPUnit_Framework_TestCase
{
    const PREFIX_SEMI_FORMAL = 'semi_formal:';

    const PREFIX_AD_HOC = 'ad_hoc:';

    /**
     * @var GroupNameFormatter
     */
    private $nameFormatter;

    protected function setUp()
    {
        $this->nameFormatter = new GroupNameFormatter(self::PREFIX_SEMI_FORMAL, self::PREFIX_AD_HOC);
    }

    /**
     * @test
     */
    public function shouldUsePrefixForAdHocGroup()
    {
        $group = new Group(1);
        $group->setName('foo');
        $group->setType(Group::TYPE_AD_HOC);

        $this->assertSame(self::PREFIX_AD_HOC, $this->nameFormatter->getPrefix($group));
    }

    /**
     * @test
     */
    public function shouldUsePrefixForSemiFormalGroup()
    {
        $group = new Group(1);
        $group->setName('foo');
        $group->setType(Group::TYPE_SEMI_FORMAL);

        $this->assertSame(self::PREFIX_SEMI_FORMAL, $this->nameFormatter->getPrefix($group));
    }

    /**
     * @test
     */
    public function shouldNotUsePrefixForFormalGroup()
    {
        $group = new Group(1);
        $group->setName('foo');
        $group->setType(Group::TYPE_FORMAL);

        $this->assertSame('', $this->nameFormatter->getPrefix($group));
    }

    /**
     * @test
     */
    public function shouldCombinePrefixNameAndIdToReturnCommonName()
    {
        $group = new Group(1);
        $group->setName('FooBar');
        $group->setType(Group::TYPE_AD_HOC);

        $this->assertSame('ad_hoc:FooBar_1', $this->nameFormatter->getCommonName($group));
    }

    /**
     * @test
     */
    public function shouldRemoveSpecialCharactersFromCommonName()
    {
        $group = new Group(1);
        $group->setName('"/\[]FooBar:;|=,+*?<>');
        $group->setType(Group::TYPE_AD_HOC);

        $this->assertSame('ad_hoc:FooBar_1', $this->nameFormatter->getCommonName($group));
    }

    /**
     * @test
     */
    public function shouldLimitLongCommonNameTo64CharactersButKeepPrefixAndSuffix()
    {
        $group = new Group(1);
        $group->setName('AGroupNameConsistingOf79CharactersSoItWillHaveToBeShortenedToFitInTheCommonName');
        $group->setType(Group::TYPE_SEMI_FORMAL);

        $commonName = $this->nameFormatter->getCommonName($group);

        $this->assertSame(64, strlen($commonName));
        $this->assertStringStartsWith(self::PREFIX_SEMI_FORMAL, $commonName);
        $this->assertStringEndsWith('_1', $commonName);
    }
}
