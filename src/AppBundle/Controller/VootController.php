<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VootController
 *
 * @Route("/voot")
 */
class VootController extends Controller
{
    /**
     * @Route("/user/{loginName}/groups", name="voot_groups")
     * @Method("GET")
     *
     * @param string $loginName
     *
     * @return Response
     */
    public function groupsAction($loginName)
    {
        $user = $this->get('app.user_manager')->getUserByLoginName($loginName);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $memberships = $this->get('app.membership_manager')->findUserMemberships($user->getId(), 0, 9999);

        $result = [];

        foreach ($memberships as $membership) {
            if ($membership->getRole() === 'prospect') {
                continue;
            }

            $result[] = [
                'id'          => $membership->getGroup()->getId(),
                'displayName' => $membership->getGroup()->getName(),
                'description' => $membership->getGroup()->getDescription(),
                'sourceID'    => $membership->getGroup()->getReference(),
                'type'        => $this->mapGroupType($membership->getGroup()->getType()),
                'membership'  => [
                    'basic' => $membership->getRole(),
                ],
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/user/{loginName}/groups/{groupId}", name="voot_group")
     * @Method("GET")
     *
     * @param string $loginName
     * @param int    $groupId
     *
     * @return Response
     */
    public function groupAction($loginName, $groupId)
    {
        $user = $this->get('app.user_manager')->getUserByLoginName($loginName);

        if (empty($user)) {
            throw $this->createNotFoundException('User not found');
        }

        $membership = $this->get('app.membership_manager')->findUserMembershipOfGroup($groupId, $user->getId());

        if ($membership === null) {
            throw $this->createNotFoundException();
        }

        $result = [
            'basic' => $membership->getRole(),
        ];

        return new JsonResponse($result);
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function mapGroupType($type)
    {
        $mapping = [
            'formal'   => 'grouphub:org',
            'ldap'     => 'grouphub:orgunit',
            'grouphub' => 'grouphub:adhoc',
        ];

        return $mapping[$type];
    }
}
