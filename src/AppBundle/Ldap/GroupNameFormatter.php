<?php

namespace AppBundle\Ldap;

use AppBundle\Model\Group;

final class GroupNameFormatter
{
    const MAX_LENGTH = 64;

    /**
     * @var string
     */
    private $semiFormalGroupPrefix = '';

    /**
     * @var string
     */
    private $adHocGroupPrefix = '';

    /**
     * @param string $semiFormalGroupPrefix
     * @param string $adHocGroupPrefix
     */
    public function __construct($semiFormalGroupPrefix, $adHocGroupPrefix)
    {
        $this->semiFormalGroupPrefix = $semiFormalGroupPrefix;
        $this->adHocGroupPrefix = $adHocGroupPrefix;
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    public function getCommonName(Group $group)
    {
        $name = str_replace(
            ['"', '/', '\\', '[', ']', ':', ';', '|', '=', ',', '+', '*', '?', '<', '>'],
            '',
            $group->getName()
        );

        $prefix = $this->getPrefix($group);

        $maxLength = self::MAX_LENGTH - strlen($prefix . '_' . $group->getId());

        return sprintf('%s%s_%s', $prefix, substr($name, 0, $maxLength), $group->getId());
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    public function getPrefix(Group $group)
    {
        if ($group->isOfType(Group::TYPE_SEMI_FORMAL)) {
            return $this->semiFormalGroupPrefix;
        }

        if ($group->isOfType(Group::TYPE_AD_HOC)) {
            return $this->adHocGroupPrefix;
        }

        return '';
    }
}
