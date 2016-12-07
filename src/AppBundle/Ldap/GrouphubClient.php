<?php

namespace AppBundle\Ldap;

use AppBundle\Model\Group;
use AppBundle\Model\User;
use AppBundle\Sequence;
use AppBundle\SynchronizableSequence;
use Doctrine\Common\Comparable;
use InvalidArgumentException;

/**
 * Class GrouphubClient
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class GrouphubClient
{
    /**
     * @var LdapClient
     */
    private $readLdap;

    /**
     * @var LdapClient
     */
    private $writeLdap;

    /**
     * @var LdapClients
     */
    private $fallbackLdaps;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var string[]
     */
    private $usersDn;

    /**
     * @var string[]
     */
    private $groupsDn;

    /**
     * @var string
     */
    private $grouphubDn;

    /**
     * @var string
     */
    private $formalDn;

    /**
     * @var string
     */
    private $adhocDn;

    /**
     * @var string
     */
    private $adminGroupsDn;

    /**
     * @var string
     */
    private $userQuery;

    /**
     * @var string
     */
    private $groupQuery;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @param LdapClient  $readLdap
     * @param LdapClient  $writeLdap
     * @param LdapClients $fallbackLdaps
     * @param Normalizer  $normalizer
     * @param string[]    $usersDn
     * @param string[]    $groupsDn
     * @param string      $grouphubDn
     * @param string      $formalDn
     * @param string      $adhocDn
     * @param string      $adminGroupsDn
     * @param string      $userQuery
     * @param string      $groupQuery
     * @param array $mapping
     */
    public function __construct(
        LdapClient $readLdap,
        LdapClient $writeLdap,
        LdapClients $fallbackLdaps,
        $normalizer,
        array $usersDn,
        array $groupsDn,
        $grouphubDn,
        $formalDn,
        $adhocDn,
        $adminGroupsDn = '',
        $userQuery = 'cn=*',
        $groupQuery = 'cn=*',
        array $mapping = []
    ) {
        $this->readLdap = $readLdap;
        $this->writeLdap = $writeLdap;
        $this->fallbackLdaps = $fallbackLdaps;

        $this->normalizer = $normalizer;

        $this->usersDn = $usersDn;
        $this->groupsDn = $groupsDn;
        $this->grouphubDn = $grouphubDn;
        $this->formalDn = $formalDn;
        $this->adhocDn = $adhocDn;
        $this->adminGroupsDn = $adminGroupsDn;
        $this->userQuery = $userQuery;
        $this->groupQuery = $groupQuery;
        $this->mapping = $mapping;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Sequence|User[]
     */
    public function findUsers($offset, $limit)
    {
        return $this->findEntities(
            $this->usersDn,
            $this->userQuery,
            $this->normalizer->getUserFields(),
            $offset,
            $limit,
            function ($data) {
                return $this->normalizer->denormalizeUsers($data);
            }
        );
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Sequence|Group[]
     */
    public function findGroups($offset, $limit)
    {
        return $this->findEntities(
            $this->groupsDn,
            $this->groupQuery,
            $this->normalizer->getGroupFields(),
            $offset,
            $limit,
            function ($data) {
                return $this->normalizer->denormalizeGroups($data);
            }
        );
    }

    /**
     * @param array|string $dns
     * @param string       $query
     * @param array        $filter
     * @param int          $offset
     * @param int          $limit
     * @param \Closure     $normalizer
     * @param bool         $useFallback
     * @param bool         $useWriteClient
     *
     * @return Sequence
     */
    private function findEntities(
        $dns,
        $query,
        $filter,
        $offset,
        $limit,
        \Closure $normalizer,
        $useFallback = false,
        $useWriteClient = false
    ) {
        $entities = [];

        foreach ((array)$dns as $dn) {
            $newEntities = $this->doFind(
                $useWriteClient ? $this->writeLdap : $this->readLdap,
                $dn,
                $query,
                $filter,
                $normalizer
            );

            if (empty($newEntities) && $useFallback && $fallbackLdap = $this->getFallbackLdapClient($dn)) {
                $newEntities = $this->doFind($fallbackLdap, $dn, $query, $filter, $normalizer);
            }

            $entities = array_merge($entities, $newEntities);
        }

        usort(
            $entities,
            function (Comparable $a, Comparable $b) {
                return $a->compareTo($b);
            }
        );

        $entities = array_slice($entities, $offset, $limit);

        $mapping = reset($entities) instanceof Group ? $this->mapping['group'] : [];

        return new SynchronizableSequence($entities, $mapping);
    }

    /**
     * @param LdapClient $client
     * @param string     $dn
     * @param string     $query
     * @param array      $filter
     * @param \Closure   $normalizer
     *
     * @return array
     */
    private function doFind(LdapClient $client, $dn, $query, $filter, \Closure $normalizer)
    {
        $data = $client->find($dn, $query, $filter);

        if (empty($data)) {
            return [];
        }

        return $normalizer($data);
    }

    /**
     * @param string $groupReference
     * @param int    $offset
     * @param int    $limit
     *
     * @return SynchronizableSequence
     */
    public function findGroupUsers($groupReference, $offset, $limit)
    {
        return $this->findEntities(
            $groupReference,
            'cn=*',
            ['member'],
            $offset,
            $limit,
            function ($data) {
                return $this->normalizer->denormalizeGroupUsers($data);
            },
            true
        );
    }

    /**
     * @param string $groupReference
     * @param int    $offset
     * @param int    $limit
     *
     * @return SynchronizableSequence
     */
    public function findGrouphubGroupUsers($groupReference, $offset, $limit)
    {
        return $this->findEntities(
            $groupReference,
            'cn=*',
            ['member'],
            $offset,
            $limit,
            function ($data) {
                return $this->normalizer->denormalizeGroupUsers($data);
            },
            true,
            true
        );
    }

    /**
     * @param Group $group
     * @param int   $offset
     * @param int   $limit
     *
     * @return SynchronizableSequence
     */
    public function findGroupAdmins(Group $group, $offset, $limit)
    {
        return $this->findGroupUsers($this->getAdminGroupReference($group), $offset, $limit);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return SynchronizableSequence
     */
    public function findGrouphubGroups($offset, $limit)
    {
        return $this->findEntities(
            $this->grouphubDn,
            'cn=*',
            $this->normalizer->getGroupFields(),
            $offset,
            $limit,
            function ($data) {
                return $this->normalizer->denormalizeGrouphubGroups($data);
            },
            false,
            true
        );
    }

    /**
     * @param array $groupIds
     *
     * @return SynchronizableSequence
     */
    public function findGrouphubGroupsByIds(array $groupIds = [])
    {
        if (empty($groupIds)) {
            return new SynchronizableSequence([]);
        }

        $query = '(|(cn=*_' . implode(')(cn=*_', $groupIds) . '))';

        return $this->findEntities(
            $this->grouphubDn,
            $query,
            $this->normalizer->getGroupFields(),
            0,
            null,
            function ($data) {
                return $this->normalizer->denormalizeGrouphubGroups($data);
            }
        );
    }

    /**
     * @param Group $group
     * @param bool  $syncAdminGroup
     *
     * @return Group
     */
    public function addGroup(Group $group, $syncAdminGroup = false)
    {
        $group->setReference($this->getGroupReference($group));

        $data = $this->normalizer->normalizeGroup($group);

        $this->writeLdap->add($group->getReference(), $data);

        if ($syncAdminGroup) {
            $this->addAdminGroupIfNotExists($group);
        }

        return $group;
    }

    /**
     * @param Group $group
     */
    public function addAdminGroupIfNotExists(Group $group)
    {
        $data = $this->normalizer->normalizeGroup($group);

        try {
            $this->writeLdap->add($this->getAdminGroupReference($group), $data);
        } catch (\Exception $e) {
            if (stripos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    /**
     * @param Group $oldGroup
     * @param Group $newGroup
     * @param bool  $syncAdminGroup
     */
    public function updateGroup(Group $oldGroup, Group $newGroup, $syncAdminGroup = false)
    {
        $groupReference = $oldGroup->getReference();

        $data = $this->normalizer->normalizeGroupForUpdate($newGroup);

        $this->writeLdap->modify($groupReference, $data);

        if ($syncAdminGroup) {
            $this->writeLdap->modify($this->getAdminGroupReference($oldGroup), $data);
        }
    }

    /**
     * @param Group $group
     * @param bool  $syncAdminGroup
     */
    public function removeGroup(Group $group, $syncAdminGroup = false)
    {
        $this->writeLdap->delete($group->getReference());

        if ($syncAdminGroup) {
            try {
                $this->writeLdap->delete($this->getAdminGroupReference($group));
            } catch (\Exception $e) {
                if (stripos($e->getMessage(), 'No such object') === false) {
                    throw $e;
                }
            }
        }
    }

    /**
     * @param string $groupReference
     * @param string $userReference
     */
    public function addGroupUser($groupReference, $userReference)
    {
        $this->writeLdap->addAttribute($groupReference, ['member' => $userReference]);
    }

    /**
     * @param Group  $group
     * @param string $userReference
     */
    public function addGroupAdmin(Group $group, $userReference)
    {
        $this->addGroupUser($this->getAdminGroupReference($group), $userReference);
    }

    /**
     * @param string $groupReference
     * @param string $userReference
     */
    public function removeGroupUser($groupReference, $userReference)
    {
        $this->writeLdap->deleteAttribute($groupReference, ['member' => $userReference]);
    }

    /**
     * @param Group  $group
     * @param string $userReference
     */
    public function removeGroupAdmin(Group $group, $userReference)
    {
        $this->removeGroupUser($this->getAdminGroupReference($group), $userReference);
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    private function getGroupReference(Group $group)
    {
        $dn = null;
        switch ($group->getType()) {
            case Group::TYPE_FORMAL:
                $dn = $this->formalDn;
                break;
            case Group::TYPE_GROUPHUB:
                $dn = $this->adhocDn;
                break;
            default:
                throw new InvalidArgumentException('Invalid group');
        }

        $group = $this->normalizer->normalizeGroup($group);

        $cn = $this->readLdap->escape($group['cn'], '', LDAP_ESCAPE_DN);

        return 'cn=' . $cn . ',' . $dn;
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    private function getAdminGroupReference(Group $group)
    {
        $pos = strpos($group->getReference(), ',');

        return substr($group->getReference(), 0, $pos) . '_admins,' . $this->adminGroupsDn;
    }

    /**
     * @param string $dn
     *
     * @return LdapClient
     */
    private function getFallbackLdapClient($dn)
    {
        $parts = ldap_explode_dn($dn, 0);

        $alias = [];
        foreach ($parts as $part) {
            if (stripos($part, 'dc=') !== 0) {
                continue;
            }

            $alias[] = substr($part, 3);
        }

        $alias = implode('.', $alias);

        try {
            return $this->fallbackLdaps->getClient($alias);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}
