<?php

namespace AppBundle\Model;

use Doctrine\Common\Comparable;
use Hslavich\SimplesamlphpBundle\Security\Core\User\SamlUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements Comparable, UserInterface, EquatableInterface, SamlUserInterface
{
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
     */
    private $firstName;

    /**
     * @var string
     */
    private $lastName;

    /**
     * @var string
     */
    private $displayName;

    /**
     * @var string
     */
    private $loginName;

    /**
     * @var string|null
     */
    private $emailAddress;

    /**
     * @var string|null
     */
    private $avatarUrl;

    /**
     * @var string[]
     *
     * Contains "extra" attributes, that can be defined per institution and have no meaning in the application itself
     */
    private $extraAttributes = [];

    /**
     * @param int         $id
     * @param string      $reference
     * @param string      $firstName
     * @param string      $lastName
     * @param string      $displayName
     * @param string      $loginName
     * @param string|null $emailAddress
     * @param string|null $avatarUrl
     * @param array       $extraAttributes
     */
    public function __construct(
        $id = null,
        $reference = '',
        $firstName = '',
        $lastName = '',
        $displayName = '',
        $loginName = '',
        $emailAddress = null,
        $avatarUrl = null,
        array $extraAttributes = []
    ) {
        $this->id = $id;
        $this->reference = $reference;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->displayName = $displayName;
        $this->loginName = $loginName;
        $this->emailAddress = $emailAddress;
        $this->avatarUrl = $avatarUrl;
        $this->extraAttributes = $extraAttributes;
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
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    public function getLoginName()
    {
        return $this->loginName;
    }

    /**
     * @return string|null
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     *
     * @return void
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->avatarUrl;
    }

    /**
     * @return array
     */
    public function getExtraAttributes()
    {
        return $this->extraAttributes;
    }

    /**
     * @inheritdoc
     *
     * @param User $other
     */
    public function compareTo($other)
    {
        $ref1 = strtoupper($this->getReference());
        $ref2 = strtoupper($other->getReference());

        $c = new \Collator('en');

        return $c->compare($ref1, $ref2);
    }

    /**
     * @param User $other
     *
     * @return bool
     */
    public function equals($other)
    {
        if ($this->compareTo($other) !== 0) {
            return false;
        }

        if ($other->getFirstName() !== $this->firstName) {
            return false;
        }

        if ($other->getLastName() !== $this->lastName) {
            return false;
        }

        if ($other->getLoginName() !== $this->loginName) {
            return false;
        }

        if ($other->getDisplayName() !== $this->getDisplayName()) {
            return false;
        }

        if ($other->getEmailAddress() !== $this->getEmailAddress()) {
            return false;
        }

        if ($other->getAvatarUrl() !== $this->getAvatarUrl()) {
            return false;
        }

        if ($other->getExtraAttributes() !== $this->getExtraAttributes()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * @inheritdoc
     */
    public function getPassword()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getUsername()
    {
        return $this->getLoginName();
    }

    /**
     * @inheritdoc
     */
    public function eraseCredentials()
    {
        // nothing here..
    }

    /**
     * @inheritdoc
     */
    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function setSamlAttributes(array $attributes)
    {
        if (isset($attributes['urn:mace:dir:attribute-def:mail'][0])) {
            $this->emailAddress = $attributes['urn:mace:dir:attribute-def:mail'][0];
        }
    }
}
