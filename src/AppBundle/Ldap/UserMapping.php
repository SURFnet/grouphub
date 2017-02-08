<?php

namespace AppBundle\Ldap;

final class UserMapping
{
    /**
     * @var array
     */
    private $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->mapping[$fieldName]);
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    public function getLdapAttributeName($fieldName)
    {
        return $this->mapping[$fieldName];
    }
}
