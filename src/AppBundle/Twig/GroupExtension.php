<?php

namespace AppBundle\Twig;

use AppBundle\Ldap\GroupNameFormatter;
use AppBundle\Model\Group;
use Twig_Extension;
use Twig_SimpleFilter;

final class GroupExtension extends Twig_Extension
{
    /**
     * @var GroupNameFormatter
     */
    private $nameFormatter;

    public function __construct(GroupNameFormatter $nameFormatter)
    {
        $this->nameFormatter = $nameFormatter;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('group_common_name', [$this, 'getCommonName']),
            new Twig_SimpleFilter('group_prefix', [$this, 'getPrefix']),
        ];
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    public function getCommonName(Group $group)
    {
        return $this->nameFormatter->getCommonName($group);
    }

    /**
     * @param Group $group
     *
     * @return string
     */
    public function getPrefix(Group $group)
    {
        return $this->nameFormatter->getPrefix($group);
    }
}
