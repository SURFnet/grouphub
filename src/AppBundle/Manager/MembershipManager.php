<?php

namespace AppBundle\Manager;

use AppBundle\Api\ApiClient;
use AppBundle\Model\Collection;
use AppBundle\Model\Group;
use AppBundle\Model\Membership;
use AppBundle\Model\SortOrder;
use AppBundle\Model\User;
use AppBundle\Service\QueueService;
use Traversable;

/**
 * Class MembershipManager
 */
class MembershipManager
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
     * @param int $userId
     * @param int $offset
     * @param int $limit
     *
     * @return Membership[]
     */
    public function findUserMemberships($userId, $offset = 0, $limit = 100)
    {
        return $this->client->findUserMemberships($userId, new SortOrder('name'), '', $offset, $limit);
    }

    /**
     * @param int    $id
     * @param string $query
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection|Membership[]
     */
    public function findGroupMemberships($id, $query = null, $offset = 0, $limit = 100)
    {
        return $this->client->findGroupMemberships($id, $query, $offset, $limit);
    }

    /**
     * @param int                $id
     * @param User[]|Traversable $users
     *
     * @return Membership[]
     */
    public function findGroupMembershipsForUsers($id, Traversable $users)
    {
        $userIds = [];
        foreach ($users as $user) {
            $userIds[] = $user->getId();
        }

        /** @var Membership[] $memberships */
        $memberships = $this->client->findGroupMembershipsForUsers($id, $userIds);

        $result = [];
        foreach ($memberships as $membership) {
            $result[$membership->getUser()->getId()] = $membership;
        }

        return $result;
    }

    /**
     * @param int $groupId
     * @param int $userId
     *
     * @return Membership
     */
    public function findUserMembershipOfGroup($groupId, $userId)
    {
        return $this->client->findUserMembershipOfGroup($userId, $groupId);
    }

    /**
     * @param int     $userId
     * @param Group[] $groups
     *
     * @return Membership[]
     */
    public function findUserMembershipOfGroups($userId, array $groups)
    {
        $groupIds = [];
        foreach ($groups as $group) {
            $groupIds[] = $group->getId();
        }

        $groupIds = array_unique($groupIds);

        if (empty($groupIds)) {
            return [];
        }

        $memberships = $this->client->findUserMembershipOfGroups($userId, array_unique($groupIds));

        $result = [];
        foreach ($memberships as $membership) {
            $result[$membership->getGroup()->getId()] = $membership;
        }

        return $result;
    }

    /**
     * @param int    $groupId
     * @param int    $userId
     * @param string $role
     */
    public function updateMembership($groupId, $userId, $role)
    {
        $this->client->updateGroupUser($groupId, $userId, $role);

        $this->queue->addGroupToQueue($groupId);
    }

    /**
     * @param int $groupId
     * @param int $userId
     */
    public function deleteMembership($groupId, $userId)
    {
        $this->client->removeGroupUser($groupId, $userId);

        $this->queue->addGroupToQueue($groupId);
    }

    /**
     * @param int $groupId
     * @param int $userId
     */
    public function addMembership($groupId, $userId)
    {
        $this->client->addGroupUser($groupId, $userId);

        $this->queue->addGroupToQueue($groupId);
    }

    /**
     * @param int    $groupId
     * @param int    $userId
     * @param string $message
     */
    public function requestMembership($groupId, $userId, $message)
    {
        $this->client->addGroupUser($groupId, $userId, Membership::ROLE_PROSPECT, $message);
    }
}
