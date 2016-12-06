<?php

namespace AppBundle\Controller;

use AppBundle\Form\GroupType;
use AppBundle\Model\Collection;
use AppBundle\Model\SortOrder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class IndexController
 */
class IndexController extends Controller
{
    /**
     * @Route("/{_locale}", defaults={"_locale": "en"}, requirements={"_locale": "en|nl"}, name="home")
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $addForm = $this->createForm(
            GroupType::class,
            null,
            [
                'action' => $this->generateUrl('add_group'),
            ]
        );

        return $this->render(
            '::base.html.twig',
            array_merge(
                $this->getGroups($request->cookies),
                [
                    'add_form' => $addForm->createView(),
                ]
            )
        );
    }

    /**
     * @Route("/{_locale}/groups", defaults={"_locale": "en"}, requirements={"_locale": "en|nl"}, name="groups")
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function groupsAction(Request $request)
    {
        $type = $request->query->get('type');
        $query = $request->query->get('query');
        $sort = $request->query->get('sort', 'name');
        $offset = $request->query->get('offset', 0);
        $limit = $request->query->get('limit', 12);

        if (!in_array($sort, ['name', 'timestamp', '-name', '-timestamp'])) {
            throw new BadRequestHttpException();
        }

        return $this->render(
            $this->getTemplate($type),
            $this->getGroups($request->cookies, $query, $sort, $offset, $limit, $type)
        );
    }

    /**
     * @param ParameterBag $cookies
     * @param string       $searchQuery
     * @param string       $signedOrder
     * @param int          $offset
     * @param int          $limit
     * @param string       $type
     *
     * @return array
     */
    private function getGroups(ParameterBag $cookies, $searchQuery = '', $signedOrder = 'name', $offset = 0, $limit = 12, $type = null)
    {
        $myGroupsSortOrder = $this->createSortOrder($this->findSignedOrderInCookie($cookies, 'my_groups'), $signedOrder);
        $myGroups = $this->getMyGroups($type, $myGroupsSortOrder, $offset, $limit);
        $groupManager = $this->get('app.group_manager');

        $allGroups = new Collection();
        $allGroupsSortOrder = $this->createSortOrder($this->findSignedOrderInCookie($cookies, 'all_groups'), $signedOrder);
        if ($type === null || $type === 'all' || $type === 'all-groups') {
            $allGroups = $groupManager->findGroups(null, null, $offset, $limit, $allGroupsSortOrder);
        }

        $organisationGroups = new Collection();
        $organisationGroupsSortOrder = $this->createSortOrder($this->findSignedOrderInCookie($cookies, 'organisation_groups'), $signedOrder);
        if (!empty($searchQuery) && ($type === null || $type === 'search' || $type === 'results')) {
            $organisationGroups = $groupManager->findGroups($searchQuery, null, $offset, $limit, $organisationGroupsSortOrder);
        }

        $memberships = $this->get('app.membership_manager')->findUserMembershipOfGroups(
            $this->getUser()->getId(),
            array_merge($allGroups->toArray(), $organisationGroups->toArray())
        );

        return [
            'myGroups'      => ['sort'=> $myGroupsSortOrder->toSignedOrder(), 'collection' => $myGroups],
            'allGroups'     => ['sort'=> $allGroupsSortOrder->toSignedOrder(), 'collection' => $allGroups],
            'organisationGroups' => ['sort'=> $organisationGroupsSortOrder->toSignedOrder(), 'collection' => $organisationGroups],
            'memberships'   => $memberships,
            'offset'        => $offset,
            'limit'         => $limit,
            'query'         => $searchQuery,
            'type'          => $type,
            'visibleGroups' => $this->parsePanelsCookie($cookies),
        ];
    }

    /**
     * @param string $type
     * @param SortOrder   $sortOrder
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getMyGroups($type, SortOrder $sortOrder, $offset, $limit)
    {
        $groupManager = $this->get('app.group_manager');

        switch ($type) {
            case null:
                return $groupManager->getMyGroups($this->getUser()->getId(), null, null, $sortOrder, 0, 4);

            case 'my':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'grouphub', null, $sortOrder, 0, 4);

            case 'my-owner':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'grouphub', 'owner', $sortOrder, $offset, $limit);

            case 'my-admin':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'grouphub', 'admin', $sortOrder, $offset, $limit);

            case 'my-member':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'grouphub', 'member', $sortOrder, $offset, $limit);

            case 'org':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'other', null, $sortOrder, 0, 4);

            case 'org-owner':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'other', 'owner', $sortOrder, $offset, $limit);

            case 'org-admin':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'other', 'admin', $sortOrder, $offset, $limit);

            case 'org-member':
                return $groupManager->getMyGroups($this->getUser()->getId(), 'other', 'member', $sortOrder, $offset, $limit);

            default:
                return new Collection();
        }
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getTemplate($type)
    {
        if ($type === null) {
            return 'groups.html.twig';
        }

        $mapping = [
            'my'         => ':groups:my_groups.html.twig',
            'my-owner'   => ':groups:my_groups-groups.html.twig',
            'my-admin'   => ':groups:my_groups-groups.html.twig',
            'my-member'  => ':groups:my_groups-groups.html.twig',
            'org'        => ':groups:organisation_groups.html.twig',
            'org-owner'  => ':groups:organisation_groups-groups.html.twig',
            'org-admin'  => ':groups:organisation_groups-groups.html.twig',
            'org-member' => ':groups:organisation_groups-groups.html.twig',
            'all'        => ':groups:all_groups.html.twig',
            'all-groups' => ':groups:all_groups-groups.html.twig',
            'search'     => ':groups:search.html.twig',
            'results'    => ':groups:search-results.html.twig',
        ];

        if (!array_key_exists($type, $mapping)) {
            throw new BadRequestHttpException();
        }

        return $mapping[$type];
    }

    /**
     * @param ParameterBag $cookies
     */
    private function parsePanelsCookie(ParameterBag $cookies)
    {
        $cookie = (array) json_decode($cookies->get('panels'), true);

        return array_merge(
            ['group_my_groups' => true, 'group_organisation_groups' => true, 'group_all_groups' => true],
            $cookie
        );
    }

    /**
     * @param $customSignedOrder
     * @param $defaultSignedOrder
     * @return SortOrder
     */
    private function createSortOrder($customSignedOrder, $defaultSignedOrder)
    {
        if ($customSignedOrder) {
            try {
                return SortOrder::createFromSignedOrder($customSignedOrder);
            } catch (\Exception $ex) {
                $this->get('logger')->warning($ex->getMessage());
            }
        }

        return SortOrder::createFromSignedOrder($defaultSignedOrder);
    }

    /**
     * @param ParameterBag $cookies
     * @param string $groupName
     * @return mixed
     */
    private function findSignedOrderInCookie(ParameterBag $cookies, $groupName)
    {
        return json_decode($cookies->get(sprintf('group_%s_sort_order', $groupName)));
    }
}
