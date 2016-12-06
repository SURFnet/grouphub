<?php

namespace AppBundle\Model;

use InvalidArgumentException;

/**
 * Sort order represents a column and a direction in which a collection can be sorted.
 * Note that the convention is to prefix the column with a '-' to denote a descending order.
 */
final class SortOrder
{
    const DIRECTION_ASCENDING = 'asc';
    const DIRECTION_DESCENDING = 'desc';
    const DIRECTION_DESCENDING_PREFIX = '-';

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $direction;

    /**
     * @param string $column
     * @param string $direction
     */
    private function __construct($column, $direction = self::DIRECTION_ASCENDING)
    {
        $this->setColumn($column);
        $this->setDirection($direction);
    }

    /**
     * @param string $signedOrder
     * @return SortOrder
     */
    public static function createFromSignedOrder($signedOrder)
    {
        if ($signedOrder[0] === self::DIRECTION_DESCENDING_PREFIX) {
            return new self(substr($signedOrder, 1), self::DIRECTION_DESCENDING);
        }

        return new self($signedOrder, self::DIRECTION_ASCENDING);
    }

    /**
     * @param $string
     * @return SortOrder
     */
    public static function ascending($string)
    {
        return new self($string, self::DIRECTION_ASCENDING);
    }

    /**
     * @param $string
     * @return SortOrder
     */
    public static function descending($string)
    {
        return new self($string, self::DIRECTION_DESCENDING);
    }

    /**
     * @return string
     */
    public function toSignedOrder()
    {
        if ($this->direction === self::DIRECTION_DESCENDING) {
            return self::DIRECTION_DESCENDING_PREFIX . $this->column;
        }

        return $this->column;
    }

    /**
     * @param string $column
     */
    private function setColumn($column)
    {
        if (!preg_match('/^[a-z-]+$/', $column)) {
            throw new InvalidArgumentException(sprintf("Cannot create sort order, '%s' is not a valid columnname", $column));
        }

        $this->column = $column;
    }

    /**
     * @param string $direction
     */
    private function setDirection($direction)
    {
        if (!in_array($direction, [self::DIRECTION_ASCENDING, self::DIRECTION_DESCENDING], true)) {
            throw new InvalidArgumentException(sprintf("Cannot create sort order, '%s' is not a valid direction", $direction));
        }

        $this->direction = $direction;
    }
}
