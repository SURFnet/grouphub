<?php
declare(strict_types=1);

namespace Tests\AppBundle\Security;

use AppBundle\Manager\MembershipManager;
use AppBundle\Model\Group;
use AppBundle\Model\Membership;
use AppBundle\Model\User;
use AppBundle\Security\GroupVoter;
use Mockery;
use PHPUnit_Framework_TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class GroupVoterTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mockery\MockInterface|MembershipManager
     */
    private $membershipManager;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Mockery\MockInterface|TokenInterface
     */
    private $token;

    protected function setUp()
    {
        $this->membershipManager = Mockery::mock(MembershipManager::class);
        $this->user = new User(123);
        $this->token = Mockery::mock(TokenInterface::class);
        $this->token->shouldReceive('getUser')->andReturn($this->user);
    }

    /**
     * @test
     *
     * @dataProvider getGroups
     */
    public function shouldAllowViewingMembersWhenVisibilityIsAll(Group $group)
    {
        $voter = new GroupVoter(
            $this->membershipManager,
            GroupVoter::VISIBILITY_ALL,
            GroupVoter::VISIBILITY_ALL,
            GroupVoter::VISIBILITY_ALL
        );

        $this->membershipManager->shouldReceive('findUserMembershipOfGroup')->andReturn(null);

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $group, ['VIEW_MEMBERS']));
    }

    /**
     * @test
     *
     * @dataProvider getGroups
     */
    public function shouldAllowViewingMembersOfTheSameGroupWhenVisibilityIsMembers(Group $group)
    {
        $voter = new GroupVoter(
            $this->membershipManager,
            GroupVoter::VISIBILITY_MEMBERS,
            GroupVoter::VISIBILITY_MEMBERS,
            GroupVoter::VISIBILITY_MEMBERS
        );

        $this->membershipManager->shouldReceive('findUserMembershipOfGroup')->andReturn(
            new Membership(Membership::ROLE_MEMBER)
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token, $group, ['VIEW_MEMBERS']));
    }

    /**
     * @test
     *
     * @dataProvider getGroups
     */
    public function shouldNotAllowViewingMembersAsAProspect(Group $group)
    {
        $voter = new GroupVoter(
            $this->membershipManager,
            GroupVoter::VISIBILITY_MEMBERS,
            GroupVoter::VISIBILITY_MEMBERS,
            GroupVoter::VISIBILITY_MEMBERS
        );

        $this->membershipManager->shouldReceive('findUserMembershipOfGroup')->andReturn(
            new Membership(Membership::ROLE_PROSPECT)
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $group, ['VIEW_MEMBERS']));
    }

    /**
     * @test
     *
     * @dataProvider getGroups
     */
    public function shouldNotAllowViewingMembersOfAGroupWhenVisibilityIsMembersAndUserIsNotAMember(Group $group)
    {
        $voter = new GroupVoter(
            $this->membershipManager,
            GroupVoter::VISIBILITY_MEMBERS,
            GroupVoter::VISIBILITY_MEMBERS,
            GroupVoter::VISIBILITY_MEMBERS
        );

        $this->membershipManager->shouldReceive('findUserMembershipOfGroup')->andReturn(null);

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $group, ['VIEW_MEMBERS']));
    }

    /**
     * @test
     *
     * @dataProvider getGroups
     */
    public function shouldNotAllowViewingMembersOfAGroupWhenVisibilityIsNone(Group $group)
    {
        $voter = new GroupVoter(
            $this->membershipManager,
            GroupVoter::VISIBILITY_NONE,
            GroupVoter::VISIBILITY_NONE,
            GroupVoter::VISIBILITY_NONE
        );

        $this->membershipManager->shouldReceive('findUserMembershipOfGroup')->andReturn(
            new Membership(Membership::ROLE_MEMBER)
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token, $group, ['VIEW_MEMBERS']));
    }

    /**
     * @return array
     */
    public function getGroups()
    {
        return [
            [new Group(123, null, null, null, Group::TYPE_FORMAL)],
            [new Group(123, null, null, null, Group::TYPE_SEMI_FORMAL)],
            [new Group(123, null, null, null, Group::TYPE_AD_HOC)],
        ];
    }
}
