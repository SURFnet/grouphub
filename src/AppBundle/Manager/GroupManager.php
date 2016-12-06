<?php

namespace AppBundle\Manager;

use AppBundle\Api\ApiClient;
use AppBundle\Model\Collection;
use AppBundle\Model\Group;
use AppBundle\Model\SortOrder;
use AppBundle\Service\QueueService;

/**
 * Class GroupManager
 */
class GroupManager
{
    /**
     * @var ApiClient
     */
    private $client;

    /**
     * @var QueueService
     */
    private $queue;

    /***
     * @param ApiClient    $client
     * @param QueueService $queue
     */
    public function __construct(ApiClient $client, QueueService $queue)
    {
        $this->client = $client;
        $this->queue = $queue;
    }

    /**
     * @param int    $userId
     * @param string $type
     * @param string $role
     * @param SortOrder   $sortOrder
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     * @todo: integrate better way of caching
     */
    public function getMyGroups($userId, $type, $role, SortOrder $sortOrder, $offset, $limit)
    {
        static $cache;

        $key = md5(json_encode(func_get_args()));

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        if ($role !== null) {
            $memberships = $this->client->findUserMembershipsForRole(
                $userId,
                $role,
                $sortOrder,
                $type,
                $offset,
                $limit
            );
        } else {
            $memberships = $this->client->findGroupedUserMemberships(
                $userId,
                $sortOrder,
                $type,
                $offset,
                $limit
            );
        }

        $cache[$key] = $memberships;

        return $memberships;
    }

    /**
     * @param string $query
     * @param string $type
     * @param int    $offset
     * @param int    $limit
     * @param SortOrder $sortOrder
     *
     * @return Collection|Group[]
     */
    public function findGroups(
        $query,
        $type,
        $offset,
        $limit,
        SortOrder $sortOrder
    ) {
        return $this->client->findGroups($query, $type, $offset, $limit, $sortOrder);
    }

    /**
     * @param int $userId
     * @param int $offset
     * @param int $limit
     *
     * @return Group[]
     * @todo: integrate better way of caching
     */
    public function findAdminGroups($userId, $offset = 0, $limit = 10)
    {
        static $aCache;

        $key = md5(json_encode(func_get_args()));

        if (isset($aCache[$key])) {
            return $aCache[$key];
        }

        $memberships = $this->client->findUserMemberships(
            $userId,
            new SortOrder('name'),
            'admin',
            $offset,
            $limit
        );

        $result = [];
        foreach ($memberships as $membership) {
            $result[$membership->getGroup()->getId()] = $membership->getGroup();
        }

        $memberships = new Collection($result, $memberships->getTotalCount());

        $aCache[$key] = $memberships;

        return $memberships;
    }

    /**
     * @param int $id
     *
     * @return Group
     */
    public function getGroup($id)
    {
        return $this->client->getGroup($id);
    }

    /**
     * @param int $id
     */
    public function deleteGroup($id)
    {
        $this->client->removeGroup($id);

        $this->queue->addGroupToQueue($id);
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group)
    {
        $group = $this->client->addGroup($group);

        $this->queue->addGroupToQueue($group->getId());
    }

    /**
     * @param Group $group
     */
    public function updateGroup(Group $group)
    {
        $this->client->updateGroup($group->getId(), $group);

        $this->queue->addGroupToQueue($group->getId());
    }
}
