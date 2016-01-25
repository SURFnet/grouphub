<?php

namespace AppBundle\Api;

use AppBundle\Model\Group;
use AppBundle\Model\User;
use AppBundle\SynchronizableSequence;
use GuzzleHttp\Client;
use RuntimeException;

/**
 * Class ApiClient
 *
 * @todo: catch (http) exceptions
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
     * @param int $offset
     * @param int $limit
     *
     * @return SynchronizableSequence
     */
    public function findUsers($offset = 0, $limit = 100)
    {
        $data = $this->guzzle->get(
            'users',
            ['query' => ['offset' => $offset, 'limit' => $limit, 'sort' => 'reference']]
        );

        $data = $this->decode($data->getBody());

        return new SynchronizableSequence($this->normalizer->denormalizeUsers($data));
    }

    /**
     * @param string $reference
     *
     * @return User
     */
    public function findUserByReference($reference)
    {
        $data = $this->guzzle->get(
            'users',
            ['query' => ['reference' => $reference]]
        );

        $data = $this->decode($data->getBody());

        if (empty($data)) {
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
        $data = $this->guzzle->get(
            'groups',
            ['query' => ['offset' => $offset, 'limit' => $limit, 'sort' => 'reference', 'type' => 'ldap']]
        );

        $data = $this->decode($data->getBody());

        return new SynchronizableSequence($this->normalizer->denormalizeGroups($data));
    }

    /**
     * @param Group $group
     * @param int   $offset
     * @param int   $limit
     *
     * @return SynchronizableSequence
     */
    public function findGroupUsers(Group $group, $offset = 0, $limit = 100)
    {
        // New group, so no Users yet
        if ($group->getId() === null) {
            return new SynchronizableSequence([]);
        }

        $data = $this->guzzle->get(
            'groups/' . $group->getId() . '/users',
            ['query' => ['offset' => $offset, 'limit' => $limit, 'sort' => 'reference']]
        );

        $data = $this->decode($data->getBody());

        return new SynchronizableSequence($this->normalizer->denormalizeGroupUsers($data));
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return SynchronizableSequence
     */
    public function findGrouphubGroups($offset = 0, $limit = 100)
    {
        $data = $this->guzzle->get(
            'groups',
            ['query' => ['offset' => $offset, 'limit' => $limit, 'sort' => 'reference', 'type' => 'grouphub']]
        );

        $data = $this->decode($data->getBody());

        return new SynchronizableSequence($this->normalizer->denormalizeGroups($data));
    }

    /**
     * @param User $user
     */
    public function addUser(User $user)
    {
        $data = $this->encode(['user' => $this->normalizer->normalizeUser($user)]);

        $this->guzzle->post('users', ['body' => $data]);
    }

    /**
     * @param Group $group
     *
     * @return Group
     */
    public function addGroup(Group $group)
    {
        $data = $this->encode(['group' => $this->normalizer->normalizeGroup($group)]);

        $data = $this->guzzle->post('groups', ['body' => $data]);

        return $this->normalizer->denormalizeGroup($this->decode($data->getBody()));
    }

    /**
     * @param int    $groupId
     * @param int    $userId
     * @param string $role
     */
    public function addGroupUser($groupId, $userId, $role = 'member')
    {
        $data = $this->encode(['userInGroup' => ['user' => $userId, 'role' => $role]]);

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
        $this->guzzle->delete('groups/' . $groupId . '/users/'  . $userId);
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