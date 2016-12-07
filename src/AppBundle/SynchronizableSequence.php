<?php

namespace AppBundle;

use Doctrine\Common\Comparable;
use OutOfBoundsException;
use RuntimeException;
use Traversable;

/**
 * Class Sequence
 */
class SynchronizableSequence extends Sequence
{
    /**
     * @var array
     */
    private $addedElements = [];

    /**
     * @var array
     */
    private $updatedElements = [];

    /**
     * @var array
     */
    private $removedElements = [];

    /**
     * @var array
     */
    private $equalElements = [];
    /**
     * @var array
     */
    private $mapping;

    public function __construct(array $elements, array $mapping)
    {
        parent::__construct($elements);
        $this->mapping = $mapping;
    }

    /**
     * @param Traversable $sourceSequence
     * @param bool        $checkOutOfBounds
     *
     * @return int
     */
    public function synchronize(Traversable $sourceSequence, $checkOutOfBounds = false)
    {
        $index = -1;

        foreach ($sourceSequence as $index => $element) {
            try {
                $this->syncIndex($index, $element, $checkOutOfBounds);
            } catch (OutOfBoundsException $e) {
                $index--;
                break;
            }
        }

        // The outer edge of this set been reached
        if ($checkOutOfBounds && count($this->addedElements) > count($this->removedElements)) {
            return $index;
        }

        // Clear all remaining elements
        for ($i = $index + 1; $i < $this->count();) {
            $this->removeElement($i, $this->offsetGet($i));
        }

        return $index;
    }

    /**
     * @return mixed[]
     */
    public function getAddedElements()
    {
        return $this->addedElements;
    }

    /**
     * @return mixed[]
     */
    public function getUpdatedElements()
    {
        return $this->updatedElements;
    }

    /**
     * @return mixed[]
     */
    public function getRemovedElements()
    {
        return $this->removedElements;
    }

    /**
     * @return mixed[]
     */
    public function getEqualElements()
    {
        return array_values($this->equalElements);
    }

    /**
     * @return int[]
     */
    public function getEqualElementIndexes()
    {
        return array_keys($this->equalElements);
    }

    /**
     * @param int   $index
     * @param mixed $sourceElement
     * @param bool  $checkOutOfBounds
     */
    private function syncIndex($index, $sourceElement, $checkOutOfBounds)
    {
        $destinationElement = $this->offsetExists($index) ? $this->offsetGet($index) : null;

        // The outer edge of this set been reached
        if ($checkOutOfBounds && $destinationElement === null &&
            count($this->removedElements) > count($this->addedElements)
        ) {
            throw new OutOfBoundsException();
        }

        // Element is new in the source and should be added to the destination
        if ($this->elementShouldBeAdded($sourceElement, $destinationElement)) {
            $this->addElement($index, $sourceElement);

            return;
        }

        // Element no longer exists in the source and should therefore be removed from the destination
        if ($this->elementShouldBeRemoved($sourceElement, $destinationElement)) {
            $this->removeElement($index, $destinationElement);
            $this->syncIndex($index, $sourceElement, $checkOutOfBounds);

            return;
        }

        // Element exists but has been modified
        if (!$this->elementsAreEqual($sourceElement, $destinationElement)) {
            $this->updateElement($index, $sourceElement);

            return;
        }

        // Elements are equal
        $this->keepElement($index);

        return;
    }

    /**
     * @param mixed $sourceElement
     * @param mixed $destinationElement
     *
     * @return bool
     */
    private function elementsAreEqual($sourceElement, $destinationElement)
    {
        if ($sourceElement instanceof Comparable) {
            if ($sourceElement->compareTo($destinationElement) !== 0) {
                throw new RuntimeException('Elements are not the same');
            }

            return $sourceElement->equals($destinationElement, $this->mapping);
        }

        return $sourceElement == $destinationElement;
    }

    /**
     * @param Comparable|mixed $sourceElement
     * @param Comparable|mixed $destinationElement
     *
     * @return bool
     */
    private function elementShouldBeRemoved($sourceElement, $destinationElement)
    {
        if ($sourceElement instanceof Comparable) {
            return $sourceElement->compareTo($destinationElement) > 0;
        }

        return $sourceElement > $destinationElement;
    }

    /**
     * @param Comparable|mixed $sourceElement
     * @param Comparable|mixed $destinationElement
     *
     * @return bool
     */
    private function elementShouldBeAdded($sourceElement, $destinationElement = null)
    {
        if ($destinationElement === null) {
            return true;
        }

        if ($sourceElement instanceof Comparable) {
            return $sourceElement->compareTo($destinationElement) < 0;
        }

        return $sourceElement < $destinationElement;
    }

    /**
     * @param int   $index
     * @param mixed $element
     */
    private function addElement($index, $element)
    {
        $this->addedElements[] = $element;

        $this->insertAt($index, $element);
    }

    /**
     * @param int   $index
     * @param mixed $element
     *
     * @todo: fix this, apply the update on the original element or return indexes?
     */
    private function updateElement($index, $element)
    {
        $this->updatedElements[] = [
            'old' => $this->offsetGet($index),
            'new' => $element,
        ];

        $this->offsetSet($index, $element);
    }

    /**
     * @param int   $index
     * @param mixed $element
     */
    private function removeElement($index, $element)
    {
        $this->removedElements[] = $element;

        $this->removeAt($index);
    }

    /**
     * @param int $index
     */
    private function keepElement($index)
    {
        $this->equalElements[$index] = $this->offsetGet($index);
    }
}
