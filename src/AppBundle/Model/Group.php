<?php

namespace AppBundle\Model;

use Assert\Assertion;
use DateTime;
use Doctrine\Common\Comparable;
use Symfony\Component\Validator\Constraints as Assert;

class Group implements Comparable
{
    /**
     * A "formal" group is a group that is synced from an external LDAP. It can therefore also be referred to as "LDAP
     * group".
     *
     * Please note that the constants were renamed to more closely follow the GroupHub naming, but the values were kept
     * the same because of legacy issues. The previous name of this constant was TYPE_LDAP.
     */
    const TYPE_FORMAL = 'ldap';

    /**
     * A "semi-formal" group is a group that is created in GroupHub, but is created by an administrator and resembles
     * an official organization (i.e. an institution)
     *
     * Together with ad-hoc groups, semi-formal groups can also be called "GroupHub groups" because they are created in
     * GroupHub.
     *
     * Please note that the constants were renamed to more closely follow the GroupHub naming, but the values were kept
     * the same because of legacy issues. The previous name of this constant was TYPE_FORMAL.
     */
    const TYPE_SEMI_FORMAL = 'formal';

    /**
     * An "ad-hoc" group is a group that can be created by everyone, and can for instance be used by a group of
     * students working on the same project.
     *
     * Together with semi-formal groups, ad-hoc groups can also be called "GroupHub groups" because they are created in
     * GroupHub.
     *
     * Please note that the constants were renamed to more closely follow the GroupHub naming, but the values were kept
     * the same because of legacy issues. The previous name of this constant was TYPE_GROUPHUB.
     */
    const TYPE_AD_HOC = 'grouphub';

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $reference;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $type;

    /**
     * @var User
     */
    private $owner;

    /**
     * @var int
     */
    private $parentId;

    /**
     * @var DateTime
     */
    private $timeStamp;

    /**
     * @var int
     */
    private $userCount;

    /**
     * @param int      $id
     * @param string   $reference
     * @param string   $name
     * @param string   $description
     * @param string   $type
     * @param User     $owner
     * @param int      $parentId
     * @param DateTime $timeStamp
     * @param int      $userCount
     */
    public function __construct(
        $id = null,
        $reference = '',
        $name = '',
        $description = '',
        $type = '',
        User $owner = null,
        $parentId = null,
        DateTime $timeStamp = null,
        $userCount = 0
    ) {
        $this->id = $id;
        $this->reference = $reference;
        $this->name = $name;
        $this->description = $description;
        $this->type = $type;
        $this->owner = $owner;
        $this->parentId = $parentId;
        $this->timeStamp = $timeStamp;
        $this->userCount = $userCount;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     */
    public function setReference($reference)
    {
        $this->reference = $reference;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isOfType($type)
    {
        Assertion::inArray($type, [self::TYPE_FORMAL, self::TYPE_SEMI_FORMAL, self::TYPE_AD_HOC]);

        return $this->type === $type;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return int
     */
    public function getOwnerId()
    {
        if (!$this->owner) {
            return null;
        }

        return $this->owner->getId();
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param int $id
     */
    public function setParentId($id)
    {
        $this->parentId = $id;
    }

    /**
     * @return DateTime
     */
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }

    /**
     * @inheritdoc
     *
     * @param Group $other
     */
    public function compareTo($other)
    {
        $ref1 = strtoupper($this->getReference());
        $ref2 = strtoupper($other->getReference());

        $c = new \Collator('en');

        return $c->compare($ref1, $ref2);
    }

    /**
     * @param Group $other
     *
     * @param array $mapping
     * @return bool
     */
    public function equals($other, array $mapping)
    {
        if ($this->compareTo($other) !== 0) {
            return false;
        }

        if (isset($mapping['name']) && $other->getName() !== $this->name) {
            return false;
        }

        if (isset($mapping['description']) && $other->getDescription() !== $this->description) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getUserCount()
    {
        return $this->userCount;
    }
}
