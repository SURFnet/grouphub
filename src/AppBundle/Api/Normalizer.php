<?php

namespace AppBundle\Api;

use AppBundle\Model\Collection;
use AppBundle\Model\Group;
use AppBundle\Model\Membership;
use AppBundle\Model\Notification;
use AppBundle\Model\User;
use DateTime;

/**
 * Class Normalizer
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Normalizer
{
    /**
     * @param User $user
     *
     * @return array
     */
    public function normalizeUser(User $user)
    {
        $annotations = [];
        foreach ($user->getAnnotations() as $key => $annotation) {
            $annotations[] = ['key' => $key, 'value' => $annotation];
        }

        return [
            'reference'   => $user->getReference(),
            'firstName'   => $user->getFirstName(),
            'lastName'    => $user->getLastName(),
            'loginName'   => $user->getLoginName(),
            'annotations' => $annotations,
        ];
    }

    /**
     * @param array $users
     *
     * @return User[]
     */
    public function denormalizeUsers(array $users)
    {
        if (!isset($users['items']) || !is_array($users['items']) || !isset($users['count'])) {
            throw new \InvalidArgumentException('Unable to denormalize users');
        }

        $result = [];
        foreach ($users['items'] as $user) {
            $result[] = $this->denormalizeUser($user);
        }

        return new Collection($result, $users['count']);
    }

    /**
     * @param array $users
     *
     * @return User[]
     */
    public function denormalizeGroupUsers(array $users)
    {
        if (!isset($users['items']) || !is_array($users['items']) || !isset($users['count'])) {
            throw new \InvalidArgumentException('Unable to denormalize group users');
        }

        $result = [];
        foreach ($users['items'] as $user) {
            $result[] = $this->denormalizeUser($user['user']);
        }

        return new Collection($result, $users['count']);
    }

    /**
     * @param array $user
     *
     * @return User
     */
    public function denormalizeUser(array $user)
    {
        $annotations = [];
        if (isset($user['annotations'])) {
            foreach ($user['annotations'] as $annotation) {
                $annotations[$annotation['attribute']] = $annotation['value'];
            }
        }

        return new User(
            $user['id'],
            $user['reference'],
            isset($user['first_name']) ? $user['first_name'] : '',
            isset($user['last_name']) ? $user['last_name'] : '',
            isset($user['login_name']) ? $user['login_name'] : '',
            $annotations
        );
    }

    /**
     * @param Group $group
     *
     * @return array
     */
    public function normalizeGroup(Group $group)
    {
        return [
            'reference'   => $group->getReference(),
            'name'        => $group->getName(),
            'description' => $group->getDescription(),
            'type'        => $group->getType(),
            'owner'       => $group->getOwnerId(),
            'parent'      => $group->getParentId(),
        ];
    }

    /**
     * @param array $groups
     *
     * @return Group[]
     */
    public function denormalizeGroups(array $groups)
    {
        if (!isset($groups['items']) || !is_array($groups['items']) || !isset($groups['count'])) {
            throw new \InvalidArgumentException('Unable to denormalize groups');
        }

        $result = [];
        foreach ($groups['items'] as $group) {
            $result[] = $this->denormalizeGroup($group);
        }

        return new Collection($result, $groups['count']);
    }

    /**
     * @param array $group
     *
     * @return Group
     */
    public function denormalizeGroup(array $group)
    {
        return new Group(
            $group['id'],
            isset($group['reference']) ? $group['reference'] : '',
            isset($group['name']) ? sprintf('%s_%s', $group['name'], $group['id']) : '',
            isset($group['description']) ? $group['description'] : '',
            isset($group['type']) ? $group['type'] : '',
            isset($group['owner']) ? $this->denormalizeUser($group['owner']) : null,
            isset($group['parent']['id']) ? $group['parent']['id'] : null,
            isset($group['timestamp']) ? new DateTime($group['timestamp']) : null,
            isset($group['user_count']) ? $group['user_count'] : 0
        );
    }

    /**
     * @param array $memberships
     *
     * @return Membership[]
     */
    public function denormalizeMemberships(array $memberships)
    {
        if (!isset($memberships['items']) || !is_array($memberships['items']) || !isset($memberships['count'])) {
            throw new \InvalidArgumentException('Unable to denormalize memberships');
        }

        $result = [];
        foreach ($memberships['items'] as $membership) {
            $result[] = $this->denormalizeMembership($membership);
        }

        return new Collection($result, $memberships['count']);
    }

    /**
     * @param array $memberships
     *
     * @return array
     */
    public function denormalizeGroupedMemberships(array $memberships)
    {
        $results = [];

        foreach ($memberships as $type => $roleMemberships) {
            foreach ($roleMemberships as $role => $collection) {
                if (!isset($collection['items']) || !is_array($collection['items']) || !isset($collection['count'])) {
                    throw new \InvalidArgumentException('Unable to denormalize memberships');
                }

                $result = [];
                foreach ($collection['items'] as $membership) {
                    $result[] = $this->denormalizeMembership($membership);
                }

                $results[$type][$role] = new Collection($result, $collection['count']);
            }
        }

        return $results;
    }

    /**
     * @param array $membership
     *
     * @return Membership
     */
    public function denormalizeMembership(array $membership)
    {
        return new Membership(
            $membership['role'],
            isset($membership['group']) ? $this->denormalizeGroup($membership['group']) : null,
            isset($membership['user']) ? $this->denormalizeUser($membership['user']) : null
        );
    }

    /**
     * @param array $notifications
     *
     * @return array
     */
    public function denormalizeNotifications(array $notifications)
    {
        $result = [];
        foreach ($notifications as $notification) {
            $result[] = $this->denormalizeNotification($notification);
        }

        return $result;
    }

    /**
     * @param array $notification
     *
     * @return Notification
     */
    private function denormalizeNotification(array $notification)
    {
        return new Notification(
            $notification['id'],
            $this->denormalizeUser($notification['from']),
            new DateTime($notification['created']),
            $notification['type'],
            isset($notification['message']) ? $notification['message'] : '',
            isset($notification['group']) ? $this->denormalizeGroup($notification['group']) : null
        );
    }
}
