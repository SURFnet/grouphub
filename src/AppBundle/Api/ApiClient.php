<?php

namespace AppBundle\Api;

use AppBundle\Model\Collection;
use AppBundle\Model\Group;
use AppBundle\Model\Membership;
use AppBundle\Model\User;
use AppBundle\Sequence;
use AppBundle\SynchronizableSequence;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;

/**
 * Class ApiClient
 *
 * @todo: catch (http) exceptions
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ApiClient
{
    /**
     * @var Client
     */
    private $guzzle;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @param Client     $guzzle
     * @param Normalizer $normalizer
     */
    public function __construct(Client $guzzle, Normalizer $normalizer)
    {
        $this->guzzle = $guzzle;
        $this->normalizer = $normalizer;
    }

    /**
     * @param string $query
     * @param string $sort
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection
     */
    public function findUsers($query = null, $sort = 'reference', $offset = 0, $limit = 100)
    {
        $data = $this->guzzle->get('users', [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => $sort,
                'query'  => $query,
            ],
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeUsers($data);
    }

    /**
     * @param string $loginName
     *
     * @return User
     */
    public function getUserByLoginName($loginName)
    {
        $data = $this->guzzle->get('users', [
            'query' => [
                'login_name' => $loginName,
            ],
        ]);

        $data = $this->decode($data->getBody());

        if (empty($data) || (isset($data['count']) && empty($data['count']))) {
            return null;
        }

        return $this->normalizer->denormalizeUser($data);
    }

    /**
     * @param string $reference
     *
     * @return User
     */
    public function findUserByReference($reference)
    {
        $data = $this->guzzle->get('users', [
            'query' => [
                'reference' => $reference,
            ],
        ]);

        $data = $this->decode($data->getBody());

        if (empty($data) || (isset($data['count']) && empty($data['count']))) {
            return null;
        }

        return $this->normalizer->denormalizeUser($data);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return SynchronizableSequence
     */
    public function findLdapGroups($offset = 0, $limit = 100)
    {
        return $this->findGroups(null, 'ldap', $offset, $limit);
    }

    /**
     * @param Group  $group
     * @param int    $offset
     * @param int    $limit
     * @param array  $roles
     *
     * @return SynchronizableSequence
     */
    public function findGroupUsers(Group $group, $offset = 0, $limit = 100, array $roles = null)
    {
        // New group, so no Users yet
        if ($group->getId() === null) {
            return new SynchronizableSequence([]);
        }

        $data = $this->guzzle->get('groups/' . $group->getId() . '/users', [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => 'reference',
                'roles'   => $roles,
            ],
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeGroupUsers($data);
    }

    /**
     * @param int    $userId
     * @param string $sortColumn
     * @param int    $sortDirection 0 => asc, 1 => desc
     * @param string $type
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection|Membership[]
     */
    public function findUserMemberships($userId, $sortColumn = 'name', $sortDirection = 0, $type = '', $offset = 0, $limit = 100)
    {
        $data = $this->guzzle->get('users/' . $userId . '/groups', [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => ($sortDirection ? '-' : '') . $sortColumn,
                'type'   => $type,
            ]
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeMemberships($data);
    }

    /**
     * @param int    $userId
     * @param string $role
     * @param string $sortColumn
     * @param int    $sortDirection
     * @param string $type
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection|Membership[]
     */
    public function findUserMembershipsForRole($userId, $role, $sortColumn = 'name', $sortDirection = 0, $type = '', $offset = 0, $limit = 100)
    {
        $data = $this->guzzle->get('users/' . $userId . '/groups/' . $role, [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => ($sortDirection ? '-' : '') . $sortColumn,
                'type'   => $type,
            ]
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeMemberships($data);
    }

    /**
     * @param int    $userId
     * @param string $sortColumn
     * @param int    $sortDirection 0 => asc, 1 => desc
     * @param string $type
     * @param int    $offset
     * @param int    $limit
     *
     * @return array
     */
    public function findGroupedUserMemberships($userId, $sortColumn = 'name', $sortDirection = 0, $type = '', $offset = 0, $limit = 10)
    {
        $data = $this->guzzle->get('users/' . $userId . '/groups/grouped', [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => ($sortDirection ? '-' : '') . $sortColumn,
                'type'   => $type,
            ]
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeGroupedMemberships($data);
    }

    /**
     * @param int    $groupId
     * @param string $query
     * @param int    $offset
     * @param int    $limit
     *
     * @return Collection
     */
    public function findGroupMemberships($groupId, $query = null, $offset = 0, $limit = 100)
    {
        $data = $this->guzzle->get('groups/' . $groupId . '/users', [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => 'name',
                'query'  => $query
            ],
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeMemberships($data);
    }

    /**
     * @param int   $groupId
     * @param int[] $userIds
     *
     * @return Collection
     */
    public function findGroupMembershipsForUsers($groupId, array $userIds)
    {
        $data = $this->guzzle->get('groups/' . $groupId . '/users', [
            'query' => [
                'sort'  => 'reference',
                'users' => $userIds
            ],
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeMemberships($data);
    }

    /**
     * @param int $userId
     * @param int $groupId
     *
     * @return Membership
     */
    public function findUserMembershipOfGroup($userId, $groupId)
    {
        $data = $this->guzzle->get('users/' . $userId . '/groups/' . $groupId);

        $data = $this->decode($data->getBody());

        if (empty($data)) {
            return null;
        }

        return $this->normalizer->denormalizeMembership($data);
    }

    /**
     * @param int   $userId
     * @param int[] $groupIds
     *
     * @return Collection
     */
    public function findUserMembershipOfGroups($userId, array $groupIds)
    {
        $data = $this->guzzle->get('users/' . $userId . '/groups', [
            'query' => [
                'groups' => $groupIds,
            ],
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeMemberships($data);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return SynchronizableSequence
     */
    public function findGrouphubGroups($offset = 0, $limit = 100)
    {
        return $this->findGroups(null, '!ldap', $offset, $limit);
    }

    /**
     * @param int[] $groupIds
     *
     * @return SynchronizableSequence
     */
    public function findGrouphubGroupsByIds(array $groupIds)
    {
        if (empty($groupIds)) {
            return new SynchronizableSequence([]);
        }

        return $this->findGroups(null, '!ldap', 0, 0, 'reference', 0, $groupIds);
    }

    /**
     * @param string $query
     * @param string $type
     * @param int    $offset
     * @param int    $limit
     * @param string $sortColumn
     * @param int    $sortDirection
     * @param int[]  $groupIds
     *
     * @return Collection
     */
    public function findGroups(
        $query = null,
        $type = null,
        $offset = 0,
        $limit = 100,
        $sortColumn = 'reference',
        $sortDirection = 0,
        array $groupIds = null
    ) {
        $data = $this->guzzle->get('groups', [
            'query' => [
                'offset' => $offset,
                'limit'  => $limit,
                'sort'   => ($sortDirection ? '-' : '') . $sortColumn,
                'type'   => $type,
                'query'  => $query,
                'ids'    => $groupIds
            ],
        ]);

        $data = $this->decode($data->getBody());

        return $this->normalizer->denormalizeGroups($data);
    }

    /**
     * @param int $id
     *
     * @return Group
     */
    public function getGroup($id)
    {
        $data = $this->guzzle->get('groups/' . $id);

        $data = $this->decode($data->getBody());

        if (empty($data)) {
            return null;
        }

        return $this->normalizer->denormalizeGroup($data);
    }

    /**
     * @param User $user
     */
    public function addUser(User $user)
    {
        $data = $this->encode(['user' => $this->normalizer->normalizeUser($user)]);

        try {
            $this->guzzle->post('users', ['body' => $data]);
        } catch (ClientException $e) {
            throw new \RuntimeException('Failed adding User to API: ' . $e->getResponse()->getBody());
        }
    }

    /**
     * @param Group $group
     *
     * @return Group
     */
    public function addGroup(Group $group)
    {
        $data = $this->encode(['group' => $this->normalizer->normalizeGroup($group)]);

        try {
            $data = $this->guzzle->post('groups', ['body' => $data]);
        } catch (ClientException $e) {
            throw new \RuntimeException('Failed adding Group to API: ' . $e->getResponse()->getBody());
        }

        return $this->normalizer->denormalizeGroup($this->decode($data->getBody()));
    }

    /**
     * @param int    $groupId
     * @param int    $userId
     * @param string $role
     * @param string $message
     */
    public function addGroupUser($groupId, $userId, $role = 'member', $message = '')
    {
        $data = $this->encode(['userInGroup' => ['user' => $userId, 'role' => $role, 'message' => $message]]);

        $this->guzzle->post('groups/' . $groupId . '/users', ['body' => $data]);
    }

    /**
     * @param int  $userId
     * @param User $user
     */
    public function updateUser($userId, User $user)
    {
        $data = $this->encode(['user' => $this->normalizer->normalizeUser($user)]);

        $this->guzzle->put('users/' . $userId, ['body' => $data]);
    }

    /**
     * @param int   $groupId
     * @param Group $group
     */
    public function updateGroup($groupId, Group $group)
    {
        $data = $this->encode(['group' => $this->normalizer->normalizeGroup($group)]);

        $this->guzzle->put('groups/' . $groupId, ['body' => $data]);
    }

    /**
     * @param int    $groupId
     * @param string $reference
     */
    public function updateGroupReference($groupId, $reference)
    {
        $data = $this->encode(['group' => ['reference' => $reference]]);

        $this->guzzle->patch('groups/' . $groupId, ['body' => $data]);
    }

    /**
     * @param int    $groupId
     * @param int    $userId
     * @param string $role
     */
    public function updateGroupUser($groupId, $userId, $role)
    {
        $data = $this->encode(['userInGroup' => ['role' => $role]]);

        $this->guzzle->put('groups/' . $groupId . '/users/' . $userId, ['body' => $data]);
    }

    /**
     * @param int $userId
     */
    public function removeUser($userId)
    {
        $this->guzzle->delete('users/' . $userId);
    }

    /**
     * @param int $groupId
     */
    public function removeGroup($groupId)
    {
        $this->guzzle->delete('groups/' . $groupId);
    }

    /**
     * @param int $groupId
     * @param int $userId
     */
    public function removeGroupUser($groupId, $userId)
    {
        try {
            $this->guzzle->delete('groups/' . $groupId . '/users/' . $userId);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() !== 404) {
                throw $e;
            }

            // Ignore not found when trying to delete the exact resource
        }
    }

    /**
     * @param int $userId
     * @param int $groupId
     *
     * @return Sequence
     */
    public function findNotifications($userId, $groupId = null)
    {
        $options = [];
        if ($groupId) {
            $options['query'] = ['group' => $groupId];
        }

        $data = $this->guzzle->get('users/' . $userId . '/notifications', $options);

        $data = $this->decode($data->getBody());

        return new Sequence($this->normalizer->denormalizeNotifications($data));
    }

    /**
     * @param int $userId
     * @param int $notificationId
     */
    public function confirmNotification($userId, $notificationId)
    {
        $data = $this->encode(['type' => 'confirm']);

        $this->guzzle->post('users/' . $userId . '/notifications/' . $notificationId . '/responses', ['body' => $data]);
    }

    /**
     * @param int $userId
     * @param int $notificationId
     */
    public function denyNotification($userId, $notificationId)
    {
        $data = $this->encode(['type' => 'deny']);

        $this->guzzle->post('users/' . $userId . '/notifications/' . $notificationId . '/responses', ['body' => $data]);
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    private function decode($data)
    {
        $data = json_decode($data, true);

        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Error decoding JSON, error no %i', $error));
        }

        return $data;
    }

    /**
     * @param mixed $data
     *
     * @return string
     */
    private function encode($data)
    {
        $data = json_encode($data);

        $error = json_last_error();
        if ($error !== JSON_ERROR_NONE) {
            throw new RuntimeException(sprintf('Error encoding JSON, error no %i', $error));
        }

        return $data;
    }
}
