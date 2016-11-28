<?php

namespace AppBundle\Ldap;

use AppBundle\Model\Group;
use AppBundle\Model\User;

/**
 * Class Normalizer
 */
class Normalizer
{
    /**
     * @var array
     */
    private $mapping;

    /**
     * @param array $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @param array $users
     *
     * @return User[]
     */
    public function denormalizeUsers(array $users)
    {
        $mapping = $this->mapping['user'];

        $result = [];
        for ($i = 0; $i < $users['count']; $i++) {
            $user = $users[$i];

            $annotations = [];

            if (isset($user[$mapping['email']][0])) {
                $annotations['email'] = $user[$mapping['email']][0];
            }

            $firstName = '';
            if (!empty($mapping['firstName']) && isset($user[$mapping['firstName']][0])) {
                $firstName = $user[$mapping['firstName']][0];
            }

            $result[] = new User(
                null,
                $user['dn'],
                $firstName,
                $user[$mapping['lastName']][0],
                $user[$mapping['loginName']][0],
                $annotations
            );
        }

        return $result;
    }

    /**
     * @param array $groups
     *
     * @return Group[]
     */
    public function denormalizeGroups(array $groups)
    {
        $mapping = $this->mapping['group'];

        $result = [];
        for ($i = 0; $i < $groups['count']; $i++) {
            $group = $groups[$i];

            $result[] = new Group(
                null,
                $group['dn'],
                $group['cn'][0],
                isset($group[$mapping['description']][0]) ? $group[$mapping['description']][0] : '',
                'ldap',
                new User(1)
            );
        }

        return $result;
    }

    /**
     * @param array $groups
     *
     * @return User[]
     */
    public function denormalizeGroupUsers(array $groups)
    {
        $result = [];
        for ($i = 0; $i < $groups['count']; $i++) {
            if (!isset($groups[$i]['member'])) {
                continue;
            }

            $group = $groups[$i]['member'];

            for ($j = 0; $j < $group['count']; $j++) {
                if (empty($group[$j])) {
                    continue;
                }

                $result[$group[$j]] = new User(null, $group[$j]);
            }
        }

        // Manually sort the results, because ldap is unable to do this
        ksort($result);

        return array_values($result);
    }

    /**
     * @param array $groups
     *
     * @return User[]
     */
    public function denormalizeGrouphubGroups(array $groups)
    {
        $mapping = $this->mapping['group'];

        $extractIdFromCn = function ($cn) {
            return substr($cn, strrpos($cn, '_') + 1);
        };

        $extractNameFromCn = function ($cn) {
            return substr($cn, 0, strrpos($cn, '_'));
        };

        $result = [];
        for ($i = 0; $i < $groups['count']; $i++) {
            $group = $groups[$i];

            $result[] = new Group(
                $extractIdFromCn($group['cn'][0]),
                $group['dn'],
                $extractNameFromCn($group['cn'][0]),
                isset($group[$mapping['description']][0]) ? $group[$mapping['description']][0] : ''
            );
        }

        return $result;
    }

    /**
     * @param Group $group
     *
     * @return array
     */
    public function normalizeGroup(Group $group)
    {
        $mapping = $this->mapping['group'];

        $cn = $this->getGroupCN($group);

        $data = array_filter([
            'cn'                    => $cn,
            $mapping['description'] => $group->getDescription(),
        ]);

        $data = array_merge($data, $mapping['extra_attributes']);

        if (!empty($mapping['accountName'])) {
            $data[$mapping['accountName']] = $cn;
        }

        if (!empty($mapping['owner'])) {
            $data[$mapping['owner']] = $group->getOwner()->getReference();
        }

        if (!empty($mapping['name'])) {
            $data[$mapping['name']] = $group->getName();
        }

        return $data;
    }

    /**
     * @param Group $group
     *
     * @return array
     */
    public function normalizeGroupForUpdate(Group $group)
    {
        $mapping = $this->mapping['group'];

        $data = array_filter(
            [
                $mapping['description'] => $group->getDescription(),
            ]
        );

        if (!empty($mapping['owner'])) {
            $data[$mapping['owner']] = $group->getOwner()->getReference();
        }

        if (!empty($mapping['name'])) {
            $data[$mapping['name']] = $group->getName();
        }

        return $data;
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    private function getGroupCN(Group $group)
    {
        $cn = str_replace(
            ['"', '/', '\\', '[', ']', ':', ';', '|', '=', ',', '+', '*', '?', '<', '>'],
            '',
            $group->getName()
        );

        $maxLength = 64 - 1 - strlen($group->getId()) - strlen($this->mapping['group']['name_prefix']);

        $cn = substr($cn, 0, $maxLength);
        $cn = $this->mapping['group']['name_prefix'] . $cn . '_' . $group->getId();

        return $cn;
    }

    /**
     * @return array
     */
    public function getGroupFields()
    {
        return [
            'dn',
            'cn',
            $this->mapping['group']['description'],
        ];
    }

    /**
     * @return array
     */
    public function getUserFields()
    {
        return [
            'dn',
            'cn',
            $this->mapping['user']['email'],
            $this->mapping['user']['firstName'],
            $this->mapping['user']['lastName'],
            $this->mapping['user']['loginName'],
        ];
    }
}
