<?php

namespace AppBundle\Ldap;

use AppBundle\Model\Group;
use AppBundle\Sequence;
use AppBundle\SynchronizableSequence;
use InvalidArgumentException;

/**
 * Class GrouphubClient
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GrouphubClient
{
    /**
     * @var LdapClient
     */
    private $ldap;

    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var string
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
     * @param LdapClient $ldap
     * @param Normalizer $normalizer
     * @param string     $usersDn
     * @param string[]   $groupsDn
     * @param string     $grouphubDn
     * @param string     $formalDn
     * @param string     $adhocDn
     * @param string     $adminGroupsDn
     */
    public function __construct(
        LdapClient $ldap,
        $normalizer,
        $usersDn,
        array $groupsDn,
        $grouphubDn,
        $formalDn,
        $adhocDn,
        $adminGroupsDn = ''
    ) {
        $this->ldap = $ldap;
        $this->normalizer = $normalizer;

        $this->usersDn = $usersDn;
        $this->groupsDn = $groupsDn;
        $this->grouphubDn = $grouphubDn;
        $this->formalDn = $formalDn;
        $this->adhocDn = $adhocDn;
        $this->adminGroupsDn = $adminGroupsDn;
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Sequence
     */
    public function findUsers($offset, $limit)
    {
        $data = $this->ldap->find($this->usersDn, 'cn=*', '*', '', $offset, $limit);

        if (empty($data)) {
            return new Sequence([]);
        }

        $users = $this->normalizer->denormalizeUsers($data);

        // @todo: use actual offset/limit
        $users = array_slice($users, $offset, $limit);

        return new Sequence($users);
    }

    /**
     * @param int $offset
     * @param int $limit
     *
     * @return Sequence
     */
    public function findGroups($offset, $limit)
    {
        $groups = [];

        foreach ($this->groupsDn as $dn) {
            $data = $this->ldap->find($dn, 'cn=*', ['cn', 'description'], '');

            if (empty($data)) {
                continue;
            }

            $groups = array_merge($groups, $this->normalizer->denormalizeGroups($data));
        }

        if (count($this->groupsDn) > 1) {
            usort(
                $groups,
                function (Group $a, Group $b) {
                    return $a->compareTo($b);
                }
            );
        }

        // @todo: use actual offset/limit
        $groups = array_slice($groups, $offset, $limit);

        return new Sequence($groups);
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
        $data = $this->ldap->find($groupReference, 'cn=*', ['member'], null, $offset, $limit);

        if (empty($data)) {
            return new SynchronizableSequence([]);
        }

        $users = $this->normalizer->denormalizeGroupUsers($data);

        // @todo: use actual offset/limit
        $users = array_slice($users, $offset, $limit);

        return new SynchronizableSequence($users);
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
        $data = $this->ldap->find($this->grouphubDn, 'cn=*', ['*'], '', $offset, $limit);

        if (empty($data)) {
            return new SynchronizableSequence([]);
        }

        $groups = $this->normalizer->denormalizeGrouphubGroups($data);

        // @todo: use actual offset/limit
        $groups = array_slice($groups, $offset, $limit);

        return new SynchronizableSequence($groups);
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

        $query = '(|(cn=*:' . implode(')(cn=*:', $groupIds) . '))';

        $data = $this->ldap->find($this->grouphubDn, $query, ['*'], '');

        if (empty($data)) {
            return new SynchronizableSequence([]);
        }

        $groups = $this->normalizer->denormalizeGrouphubGroups($data);

        return new SynchronizableSequence($groups);
    }

    /**
     * @param Group $group
     * @param bool  $syncAdminGroup
     *
     * @return Group
     */
    public function addGroup(Group $group, $syncAdminGroup = false)
    {
        $cn = $group->getName() . ':' . $group->getId();

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

        $dn = 'cn=' . strtolower($cn) . ',' . $dn;

        $group->setReference($dn);

        $data = $this->normalizer->normalizeGroup($group);

        $this->ldap->add($group->getReference(), $data);

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
            $this->ldap->add($this->getAdminGroupReference($group), $data);
        } catch (\Exception $e) {
            if (stripos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }

    /**
     * @param string $groupReference
     * @param Group  $group
     * @param bool   $syncAdminGroup
     */
    public function updateGroup($groupReference, Group $group, $syncAdminGroup = false)
    {
        $data = $this->normalizer->normalizeGroupForUpdate($group);

        $this->ldap->modify($groupReference, $data);

        if ($syncAdminGroup) {
            $this->ldap->modify($this->getAdminGroupReference($group), $data);
        }
    }

    /**
     * @param Group $group
     * @param bool  $syncAdminGroup
     */
    public function removeGroup(Group $group, $syncAdminGroup = false)
    {
        $this->ldap->delete($group->getReference());

        if ($syncAdminGroup) {
            $this->ldap->delete($this->getAdminGroupReference($group));
        }
    }

    /**
     * @param string $groupReference
     * @param string $userReference
     */
    public function addGroupUser($groupReference, $userReference)
    {
        $this->ldap->addAttribute($groupReference, ['member' => $userReference]);
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
        $this->ldap->deleteAttribute($groupReference, ['member' => $userReference]);
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
    private function getAdminGroupReference(Group $group)
    {
        $cn = $group->getName() . ':' . $group->getId();

        return 'cn=' . strtolower($cn) . ':admins,' . $this->adminGroupsDn;
    }
}
