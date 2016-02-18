<?php

namespace AppBundle\Manager;

use AppBundle\Api\ApiClient;
use AppBundle\Model\Membership;

/**
 * Class MembershipManager
 */
class MembershipManager
{
    /**
     * @var ApiClient
     */
    private $client;

    /***
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param int $userId
     *
     * @return Membership[]
     */
    public function findUserMemberships($userId)
    {
        return $this->client->findUserMemberships($userId);
    }

    /**
     * @param int    $id
     * @param string $query
     * @param int    $offset
     * @param int    $limit
     *
     * @return Membership[]
     */
    public function findGroupMemberships($id, $query = null, $offset = 0, $limit = 100)
    {
        return $this->client->findGroupMemberships($id, $query, $offset, $limit);
    }

    /**
     * @param int $groupId
     * @param int $userId
     *
     * @return Membership
     * @todo: a user could have multiple memberships
     */
    public function findUserMembershipOfGroup($groupId, $userId)
    {
        return $this->client->findUserMembershipOfGroup($userId, $groupId);
    }

    /**
     * @param int    $groupId
     * @param int    $userId
     * @param string $role
     */
    public function updateMembership($groupId, $userId, $role)
    {
        $this->client->updateGroupUser($groupId, $userId, $role);
    }

    /**
     * @param int $groupId
     * @param int $userId
     */
    public function deleteMembership($groupId, $userId)
    {
        $this->client->removeGroupUser($groupId, $userId);
    }

    /**
     * @param int $groupId
     * @param int $userId
     */
    public function addMembership($groupId, $userId)
    {
        $this->client->addGroupUser($groupId, $userId);
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
