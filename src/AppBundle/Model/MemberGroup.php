<?php

namespace AppBundle\Model;

/**
 * Class Membership
 */
class MemberGroup
{
    /**
     * @var User
     */
    private $memberGroup;

    /**
     * @param Group  $memberGroup
     */
    public function __construct(Group $memberGroup = null)
    {
        $this->memberGroup = $memberGroup;
    }

    /**
     * @return User
     */
    public function getMemberGroup()
    {
        return $this->memberGroup;
    }
}
