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
     * @param int $totalCount
     * @param array $mapping
     */
    public function __construct(array $elements = [], $totalCount = 0, array $mapping = [])
    {
        parent::__construct($elements, $mapping);

        $this->totalCount = $totalCount;

        if (!empty($elements) && $totalCount === 0) {
            $this->totalCount = count($elements);
        }
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        return $this->totalCount;
    }

    /**
     * @param callable $f
     * @return Collection
     */
    public function filter(callable $f)
    {
        return new self(array_filter($this->toArray(), $f));
    }
}
