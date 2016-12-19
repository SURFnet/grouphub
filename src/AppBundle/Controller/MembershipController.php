<?php

namespace AppBundle\Controller;

use AppBundle\Manager\GroupManager;
use AppBundle\Manager\MembershipManager;
use AppBundle\Model\Collection;
use AppBundle\Model\Group;
use AppBundle\Model\Membership;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class MembershipController
 */
class MembershipController extends Controller
{
    /**
     * @Route("/group/{groupId}/user/{userId}/add", name="membership_add")
     * @Method("POST")
     *
     * @param int $groupId
     * @param int $userId
     *
     * @return Response
     */
    public function addMembershipAction($groupId, $userId)
    {
        $group = $this->get('app.group_manager')->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT', $group);

        $this->get('app.membership_manager')->addMembership($groupId, $userId);

        return new Response();
    }

    /**
     * @Route("/group/{groupId}/user/{groupToAddId}/add", name="group_membership_add")
     * @Method("POST")
     *
     * @param int $groupId
     * @param int $groupToAddId
     *
     * @return Response
     */
    public function addGroupMembershipAction($groupId, $groupToAddId)
    {
        $group = $this->get('app.group_manager')->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT', $group);

        $this->get('app.membership_manager')->addGroupMembership($groupId, $groupToAddId);

        return new Response();
    }

    /**
     * @Route("/group/{groupId}/copyMembersFromGroup/{groupToCopyMembersFromId}", name="membership_copy_members_from_group")
     * @Method("POST")
     *
     * @param int $groupId
     * @param int $groupToCopyMembersFromId
     *
     * @return Response
     */
    public function addMembersFromGroupAction($groupId, $groupToCopyMembersFromId)
    {
        /** @var GroupManager $groupManager */
        $groupManager = $this->get('app.group_manager');

        /** @var Group $group */
        $group = $groupManager->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT', $group);

        /** @var MembershipManager $membershipManager */
        $membershipManager = $this->get('app.membership_manager');

        $findMemberShips = function () use ($membershipManager, $groupToCopyMembersFromId) {
            return $membershipManager
                ->findGroupMemberships($groupToCopyMembersFromId, null, 0, PHP_INT_MAX)
                ->toArray();
        };

        $mapMembersToUserIds = function (Membership $membership) {
            return $membership->getUser()->getId();
        };

        $newMemberUserIds = [];
        foreach (array_map($mapMembersToUserIds, $findMemberShips()) as $userId) {
            $membershipManager->addMembership($groupId, $userId);
            $newMemberUserIds[] = $userId;
        };

        /** @var Collection $members */
        $members = $this
            ->get('app.membership_manager')
            ->findGroupMemberships($group->getId(), null, 0, PHP_INT_MAX);

        $filterAddedMembers = function (Membership $membership) use ($newMemberUserIds) {
            return in_array($membership->getUser()->getId(), $newMemberUserIds);
        };

        return $this->render(
            ':popups:group_members.html.twig',
            [
                'group'         => $group,
                'members'       => $members->filter($filterAddedMembers),
                'notifications' => [],
                'query'         => null,
                'offset'        => 0,
                'limit'         => PHP_INT_MAX
            ]
        );
    }

    /**
     * @Route("/group/{groupId}/user/{userId}/update", name="membership_update")
     * @Method("POST")
     *
     * @param int     $groupId
     * @param int     $userId
     * @param Request $request
     *
     * @return Response
     */
    public function updateMembershipAction($groupId, $userId, Request $request)
    {
        $group = $this->get('app.group_manager')->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT', $group);

        $role = $request->request->get('role');

        if (!in_array($role, [Membership::ROLE_ADMIN, Membership::ROLE_MEMBER])) {
            throw new BadRequestHttpException('Invalid role');
        }

        $this->get('app.membership_manager')->updateMembership($groupId, $userId, $role);

        return new Response();
    }

    /**
     * @Route("/group/{groupId}/user/{userId}/delete", name="membership_delete")
     * @Method("POST")
     *
     * @param int $groupId
     * @param int $userId
     *
     * @return Response
     */
    public function deleteMembershipAction($groupId, $userId)
    {
        $group = $this->get('app.group_manager')->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT', $group);

        $this->get('app.membership_manager')->deleteMembership($groupId, $userId);

        return new Response();
    }

    /**
     * @Route("/group/{groupId}/me/add", name="my_membership_add")
     * @Method("POST")
     *
     * @param int     $groupId
     * @param Request $request
     *
     * @return Response
     */
    public function addMyMembership($groupId, Request $request)
    {
        $group = $this->get('app.group_manager')->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT_MEMBERSHIP', $group);

        $message = $request->request->get('personal_message');

        $this->get('app.membership_manager')->requestMembership($groupId, $this->getUser()->getId(), $message);

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @Route("/group/{groupId}/me/delete", name="my_membership_delete")
     * @Method("POST")
     *
     * @param int $groupId
     *
     * @return Response
     */
    public function deleteMyMembership($groupId)
    {
        $group = $this->get('app.group_manager')->getGroup($groupId);

        if (empty($group)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('EDIT_MEMBERSHIP', $group);

        $this->get('app.membership_manager')->deleteMembership($groupId, $this->getUser()->getId());

        return $this->redirect($this->generateUrl('home'));
    }
}
