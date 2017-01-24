<?php

namespace AppBundle\Security;

use AppBundle\Manager\MembershipManager;
use AppBundle\Model\Group;
use AppBundle\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Class MembershipVoter
 */
class MembershipVoter extends Voter
{
    /**
     * @var MembershipManager
     */
    private $membershipManager;

    /**
     * @param MembershipManager $membershipManager
     */
    public function __construct(MembershipManager $membershipManager)
    {
        $this->membershipManager = $membershipManager;
    }

    /**
     * @inheritdoc
     */
    protected function supports($attribute, $subject)
    {
        return ($subject instanceof Group && $attribute === 'EDIT_MEMBERSHIP');
    }

    /**
     * @inheritdoc
     *
     * @param Group $subject
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // A user is allowed to edit his own membership of grouphub groups
        if ($subject->getType() === Group::TYPE_AD_HOC) {
            return true;
        }

        return false;
    }
}
