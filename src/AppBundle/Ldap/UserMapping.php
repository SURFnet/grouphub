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

    /**
     * @return array
     */
    public function getFieldNames()
    {
        return array_keys($this->mapping);
    }

    /**
     * @return array
     */
    public function getLdapAttributeNames()
    {
        return array_values($this->mapping);
    }

    /**
     * @return array
     */
    public function getExtraFieldNames()
    {
        $standardFields = [
            'firstName',
            'lastName',
            'loginName',
            'displayName',
            'avatarUrl',
            'email',
        ];

        $extraFieldNames = array_diff($this->getFieldNames(), $standardFields);

        return array_values($extraFieldNames);
    }
}
