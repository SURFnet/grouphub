<?php

namespace AppBundle\Manager;

use AppBundle\Model\Group;

/**
 * Exporter
 */
class Exporter
{
    /**
     * @var MembershipManager
     */
    private $membershipManager;

    /**
     * Exporter constructor.
     *
     * @param MembershipManager $membershipManager
     */
    public function __construct(MembershipManager $membershipManager)
    {
        $this->membershipManager = $membershipManager;
    }

    /**
     * @param Group  $group
     * @param string $filename
     */
    public function exportGroupMembers(Group $group, $filename)
    {
        $handle = fopen($filename, 'w+');

        fputcsv($handle, ['Role', 'Id', 'Login name', 'First name', 'Last name', 'Email'], ';');

        $memberships = $this->membershipManager->findGroupMemberships($group->getId(), null, 0, 9999);

        foreach ($memberships as $membership) {
            $user = $membership->getUser();

            fputcsv(
                $handle,
                [
                    $membership->getRole(),
                    $user->getId(),
                    $user->getLoginName(),
                    $user->getFirstName(),
                    $user->getLastName(),
                    $user->getEmailAddress(),
                ],
                ';'
            );
        }

        fclose($handle);
    }
}
