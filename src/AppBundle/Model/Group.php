<?php

namespace AppBundle\Model;

use DateTime;
use Doctrine\Common\Comparable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Group
 */
class Group implements Comparable
{
    const TYPE_LDAP = 'ldap';
    const TYPE_FORMAL = 'formal';
    const TYPE_GROUPHUB = 'grouphub';

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
