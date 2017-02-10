<?php

namespace AppBundle\Security;

use AppBundle\Manager\MembershipManager;
use AppBundle\Model\Group;
use AppBundle\Model\Membership;
use AppBundle\Model\User;
use Assert\Assertion;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class GroupVoter
 */
class GroupVoter extends Voter
{
    const VISIBILITY_NONE = 'NONE';

    const VISIBILITY_MEMBERS = 'MEMBERS';

    const VISIBILITY_ALL = 'ALL';

    /**
     * @var MembershipManager
     */
    private $membershipManager;

    /**
     * @var string
     */
    private $formalMemberVisibility;

    /**
     * @var string
     */
    private $semiFormalMemberVisibility;

    /**
     * @var string
     */
    private $adHocMemberVisibility;

    /**
     * @param MembershipManager $membershipManager
     * @param string            $formalMemberVisibility
     * @param string            $semiFormalMemberVisibility
     * @param string            $adHocMemberVisibility
     */
    public function __construct(
        MembershipManager $membershipManager,
        $formalMemberVisibility,
        $semiFormalMemberVisibility,
        $adHocMemberVisibility
    ) {
        $visibilityOptions = [self::VISIBILITY_NONE, self::VISIBILITY_MEMBERS, self::VISIBILITY_ALL];

        Assertion::inArray($formalMemberVisibility, $visibilityOptions);
        Assertion::inArray($semiFormalMemberVisibility, $visibilityOptions);
        Assertion::inArray($adHocMemberVisibility, $visibilityOptions);

        $this->membershipManager = $membershipManager;
        $this->formalMemberVisibility = $formalMemberVisibility;
        $this->semiFormalMemberVisibility = $semiFormalMemberVisibility;
        $this->adHocMemberVisibility = $adHocMemberVisibility;
    }

    /**
     * @inheritdoc
     */
    protected function supports($attribute, $subject)
    {
        if (!$subject instanceof Group) {
            return false;
        }

        if (!in_array($attribute, ['EDIT', 'EDIT_DETAILS', 'VIEW_MEMBERS'], true)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     *
     * @param Group $group
     */
    protected function voteOnAttribute($attribute, $group, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($group->getOwnerId() == $user->getId()) {
            return true;
        }

        $membership = $this->membershipManager->findUserMembershipOfGroup($group->getId(), $user->getId());

        if ($attribute === 'VIEW_MEMBERS' && $this->canViewMembersOfGroup($group, $membership)) {
            return true;
        }

        if (!$membership) {
            return false;
        }

        if ($attribute === 'EDIT' && $membership->getRole() === Membership::ROLE_ADMIN) {
            return true;
        }

        return false;
    }

    private function canViewMembersOfGroup(Group $group, Membership $membership = null)
    {
        $visibility = $this->getGroupMemberVisibility($group);

        $isSemiFormalOrAdHoc = $group->isOfType(Group::TYPE_SEMI_FORMAL) || $group->isOfType(Group::TYPE_AD_HOC);

        if ($visibility === self::VISIBILITY_ALL) {
            return true;
        }

        if (!$membership instanceof Membership) {
            return false;
        }

        if ($membership->getRole() === Membership::ROLE_PROSPECT) {
            return false;
        }

        if ($isSemiFormalOrAdHoc && $membership->getRole() === Membership::ROLE_ADMIN) {
            return true;
        }

        if ($visibility === self::VISIBILITY_MEMBERS && $membership !== null) {
            return true;
        }

        return false;
    }

    private function getGroupMemberVisibility(Group $group)
    {
        if ($group->isOfType(Group::TYPE_FORMAL)) {
            return $this->formalMemberVisibility;
        }

        if ($group->isOfType(Group::TYPE_SEMI_FORMAL)) {
            return $this->semiFormalMemberVisibility;
        }

        if ($group->isOfType(Group::TYPE_AD_HOC)) {
            return $this->adHocMemberVisibility;
        }

        throw new RuntimeException(sprintf('Unexpected group type: "%s"', $group->getType()));
    }
}
