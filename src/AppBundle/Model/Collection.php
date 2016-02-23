<?php

namespace AppBundle\Model;

use AppBundle\SynchronizableSequence;

/**
 * Class Collection
 */
class Collection extends SynchronizableSequence
{
    /**
     * @var int
     */
    private $totalCount;

    /**
     * @param array $elements
     * @param int   $totalCount
     */
    public function __construct(array $elements = [], $totalCount = 0)
    {
        parent::__construct($elements);

        $this->totalCount = $totalCount;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }
}
