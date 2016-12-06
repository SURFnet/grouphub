<?php

namespace AppBundle\Model;

use InvalidArgumentException;

final class SortOrder
{
    const DIRECTION_ASCENDING = 0;
    const DIRECTION_DESCENDING = 1;

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
     * @param string $signedName
     * @return SortOrder
     */
    public static function createFromSignedName($signedName)
    {
        if ($signedName[0] === '-') {
            return new self(substr($signedName, 1), self::DIRECTION_DESCENDING);
        }

        return new self($signedName, self::DIRECTION_ASCENDING);
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
    public function getSignedName()
    {
        return ($this->direction ? '-' : '') . $this->column;
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
