<?php

namespace AppBundle\Security;

use AppBundle\Manager\GroupManager;
use AppBundle\Model\Collection;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AdminVoter
 */
class AdminVoter extends Voter
{
    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * @param GroupManager $groupManager
     */
    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    /**
     * @inheritdoc
     */
    protected function supports($attribute, $subject)
    {
        return $attribute === 'ROLE_ADMIN';
    }

    /**
     * @inheritdoc
     */
    protected function voteOnAttribute($attribute, $group, TokenInterface $token)
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Collection $groups */
        $groups = $this->groupManager->findAdminGroups($user->getId());

        return (bool)$groups->getTotalCount();
    }
}
