<?php

namespace AppBundle\Ldap;

use Assert\Assertion;

final class MappingFactory
{
    /**
     * @var array
     */
    private $mapping;

    public function __construct(array $mapping)
    {
        Assertion::keyExists($mapping, 'user');
        Assertion::isArray($mapping['user']);

        $this->mapping = $mapping;
    }

    /**
     * @return UserMapping
     */
    public function getUserMapping()
    {
        return new UserMapping($this->mapping['user']);
    }
}
