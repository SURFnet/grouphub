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
     * @var GroupNameFormatter
     */
    private $nameFormatter;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var UserMapping
     */
    private $userMapping;

    /**
     * @param GroupNameFormatter $nameFormatter
     * @param array              $mapping
     * @param UserMapping        $userMapping
     */
    public function __construct(GroupNameFormatter $nameFormatter, array $mapping, UserMapping $userMapping)
    {
        $this->mapping = $mapping;
        $this->nameFormatter = $nameFormatter;
        $this->userMapping = $userMapping;
    }

    /**
     * @param array $users
     *
     * @return User[]
     */
    public function denormalizeUsers(array $users)
    {
        $result = [];
        for ($i = 0; $i < $users['count']; $i++) {
            $user = $users[$i];

            $extraAttributes = [];
            foreach ($this->userMapping->getExtraFieldNames() as $extraFieldName) {
                $attribute = $this->getUserAttributeIfExists($user, $extraFieldName, null);

                if ($attribute !== null) {
                    $extraAttributes[$extraFieldName] = $attribute;
                }
            }

            $result[] = new User(
                null,
                $user['dn'],
                $this->getUserAttributeIfExists($user, 'firstName', ''),
                $user[$this->userMapping->getLdapAttributeName('lastName')][0],
                $this->getUserAttributeIfExists($user, 'displayName', ''),
                $user[$this->userMapping->getLdapAttributeName('loginName')][0],
                $this->getUserAttributeIfExists($user, 'email', null),
                $this->getUserAttributeIfExists($user, 'avatarUrl', null),
                $extraAttributes
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

        $result = [];
        for ($i = 0; $i < $groups['count']; $i++) {
            $group = $groups[$i];

            $result[] = new Group(
                null,
                $group['dn'],
                isset($group[$mapping['name']][0]) ? $group[$mapping['name']][0] : '',
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

        $cn = $this->nameFormatter->getCommonName($group);

        $data = array_filter(
            [
                'cn' => $cn,
                $mapping['description'] => $group->getDescription(),
            ]
        );

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

        if (!empty($mapping['description'])) {
            $data[$mapping['description']] = $group->getDescription();
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

    /**
     * @param array  $user
     * @param string $attribute
     * @param mixed  $default
     *
     * @return mixed
     */
    private function getUserAttributeIfExists(array $user, $attribute, $default = null)
    {
        if (!$this->userMapping->hasField($attribute)) {
            return $default;
        }

        $attributeName = $this->userMapping->getLdapAttributeName($attribute);

        if (!isset($user[$attributeName][0])) {
            return $default;
        }

        return $user[$attributeName][0];
    }
}
