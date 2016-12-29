<?php

namespace AppBundle\Controller;

use AppBundle\Form\GroupType;
use AppBundle\Manager\GroupManager;
use AppBundle\Manager\UserManager;
use AppBundle\Model\Group;
use AppBundle\Model\SortOrder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class GroupController
 */
class GroupController extends Controller
{
    /**
     * @Route("/{_locale}/add_group", name="add_group")
     * @Method("POST")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function addGroupAction(Request $request)
    {
        $form = $this->createForm(GroupType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('app.group_manager')->addGroup($form->getData());

            return $this->redirect($this->generateUrl('home'));
        }

        return new Response($form->getErrors(true));
    }

    /**
     * @Route("/{_locale}/group/{id}", name="group_details")
     * @Method("GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function groupDetailsAction($id)
    {
        $group = $this->getGroup($id);

        $offset = 0;
        $limit = 12;

        $members = $this->get('app.membership_manager')->findGroupMemberships($group->getId(), null, $offset, $limit);
        $memberGroups = $this->get('app.membership_manager')->findGroupMemberGroups($group->getId(), $offset, $limit);

        $users = $groups = $form = $notifications = $memberships = null;
        if ($this->isGranted('EDIT', $group)) {
            $users = $this->get('app.user_manager')->findUsers(null, $offset, $limit);
            $groups = $this->get('app.group_manager')->findGroups(null, null, $offset, $limit, SortOrder::ascending('name'));
            $memberships = $this->get('app.membership_manager')->findGroupMembershipsForUsers($group->getId(), $users);

            $notifications = $this->get('app.notification_manager')->findNotificationsForGroup(
                $this->getUser()->getId(),
                $group->getId()
            );
        }

        if ($this->isGranted('EDIT_DETAILS', $group)) {
            $form = $this->createForm(GroupType::class, $group)->createView();
        }

        return $this->render(
            ':popups:group_details.html.twig',
            [
                'group'         => $group,
                'members'       => $members,
                'memberships'   => $memberships,
                'memberGroups'  => $memberGroups,
                'users'         => $users,
                'groups'        => $groups,
                'form'          => $form,
                'notifications' => $notifications,
                'query'         => '',
                'offset'        => $offset,
                'limit'         => $limit
            ]
        );
    }

    /**
     * @Route("/{_locale}/group/{id}/edit", name="edit_group")
     * @Method("POST")
     *
     * @param int     $id
     * @param Request $request
     *
     * @return Response
     */
    public function editGroupAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $this->denyAccessUnlessGranted('EDIT_DETAILS', $group);

        $form = $this->createForm(GroupType::class, $group);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->get('app.group_manager')->updateGroup($group);

            return new Response();
        }

        return new Response($form->getErrors(true));
    }

    /**
     * @Route("/{_locale}/group/{id}/delete", name="delete_group")
     * @Method("POST")
     *
     * @param int $id
     *
     * @return Response
     */
    public function deleteGroupAction($id)
    {
        $group = $this->getGroup($id);

        $this->denyAccessUnlessGranted('EDIT_DETAILS', $group);

        $this->get('app.group_manager')->deleteGroup($group->getId());

        return $this->redirect($this->generateUrl('home'));
    }

    /**
     * @Route("/{_locale}/group/{id}/users/search", name="search_group_users")
     * @Method("GET")
     *
     * @param int     $id
     * @param Request $request
     *
     * @return Response
     */
    public function searchUsersAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $this->denyAccessUnlessGranted('EDIT', $group);

        $query = $request->query->get('query');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 12);

        /** @var UserManager $userManager */
        $userManager = $this->get('app.user_manager');
        $users = $userManager->findUsers($query, $offset, $limit);

        $members = $this->get('app.membership_manager')->findGroupMembershipsForUsers($group->getId(), $users);

        $notifications = $this->get('app.notification_manager')->findNotificationsForGroup(
            $this->getUser()->getId(),
            $group->getId()
        );

        return $this->render(
            ':popups:group_users.html.twig',
            [
                'group'         => $group,
                'members'       => $members,
                'users'         => $users,
                'notifications' => $notifications,
                'query'         => $query,
                'offset'        => $offset,
                'limit'         => $limit
            ]
        );
    }

    /**
     * @Route("/{_locale}/group/{id}/groups_from_which_members_can_be_copied/search", name="search_group_groups_from_which_members_can_be_copied")
     * @Method("GET")
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function searchGroupsFromWhichMembersCanBeCopiedAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $this->denyAccessUnlessGranted('EDIT', $group);

        $query = $request->query->get('query');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 12);

        /** @var GroupManager $groupManager */
        $groupManager = $this->get('app.group_manager');
        $groups = $groupManager->findGroups($query, null, $offset, $limit, SortOrder::ascending('name'));

        $notifications = $this->get('app.notification_manager')->findNotificationsForGroup(
            $this->getUser()->getId(),
            $group->getId()
        );

        return $this->render(
            ':popups:group_groups_from_which_members_can_be_copied.html.twig',
            [
                'group' => $group,
                'groups' => $groups,
                'notifications' => $notifications,
                'query' => $query,
                'offset' => $offset,
                'limit' => $limit
            ]
        );
    }

    /**
     * @Route("/{_locale}/group/{id}/groups/linkable", name="group_groups_linkable")
     * @Method("GET")
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     */
    public function searchGroupsLinkableAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $this->denyAccessUnlessGranted('EDIT', $group);

        $query = $request->query->get('query');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 12);

        /** @var GroupManager $groupManager */
        $groupManager = $this->get('app.group_manager');
        $groups = $groupManager->findGroupsLinkable($id, $query, null, $offset, $limit, SortOrder::ascending('name'));

        $notifications = $this->get('app.notification_manager')->findNotificationsForGroup(
            $this->getUser()->getId(),
            $group->getId()
        );

        return $this->render(
            ':popups:group_groups_linkable.html.twig',
            [
                'group' => $group,
                'groups' => $groups,
                'notifications' => $notifications,
                'query' => $query,
                'offset' => $offset,
                'limit' => $limit
            ]
        );
    }

    /**
     * @Route("/{_locale}/group/{id}/members/search", name="search_group_members")
     * @Method("GET")
     *
     * @param int     $id
     * @param Request $request
     *
     * @return Response
     */
    public function searchMembersAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $query = $request->query->get('query');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 12);

        $members = $this->get('app.membership_manager')->findGroupMemberships($group->getId(), $query, $offset, $limit);

        $notifications = null;
        if ($this->isGranted('EDIT', $group)) {
            $notifications = $this->get('app.notification_manager')->findNotificationsForGroup(
                $this->getUser()->getId(),
                $group->getId()
            );
        }

        return $this->render(
            ':popups:group_members.html.twig',
            [
                'group'         => $group,
                'members'       => $members,
                'notifications' => $notifications,
                'query'         => $query,
                'offset'        => $offset,
                'limit'         => $limit
            ]
        );
    }

    /**
     * @Route("/{_locale}/group/{id}/member_groups/search", name="search_group_member_groups")
     * @Method("GET")
     *
     * @param int     $id
     * @param Request $request
     *
     * @return Response
     */
    public function searchMemberGroupsAction($id, Request $request)
    {
        $group = $this->getGroup($id);

        $query = $request->query->get('query');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 12);

        $memberGroups = $this->get('app.membership_manager')->findGroupMemberGroups($group->getId(), $query, $offset, $limit);

        $notifications = null;
        if ($this->isGranted('EDIT', $group)) {
            $notifications = $this->get('app.notification_manager')->findNotificationsForGroup(
                $this->getUser()->getId(),
                $group->getId()
            );
        }

        return $this->render(
            ':popups:group_members.html.twig',
            [
                'group'         => $group,
                'memberGroups'  => $memberGroups,
                'notifications' => $notifications,
                'query'         => $query,
                'offset'        => $offset,
                'limit'         => $limit
            ]
        );
    }

    /**
     * @Route("/group/{id}/members/export", name="group_export_members")
     * @Method("GET")
     *
     * @param int $id
     *
     * @return Response
     */
    public function downloadMembersAction($id)
    {
        $group = $this->getGroup($id);

        $this->denyAccessUnlessGranted('EDIT', $group);

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($group) {
                $this->get('app.exporter')->exportGroupMembers($group, 'php://output');
            }
        );

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'grouphub-group-export.csv'
            )
        );

        return $response;
    }

    /**
     * @param int $id
     *
     * @return Group
     */
    private function getGroup($id)
    {
        $group = $this->get('app.group_manager')->getGroup($id);

        if (!$group) {
            throw $this->createNotFoundException('Group not found');
        }

        return $group;
    }
}
