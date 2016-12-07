<?php

namespace AppBundle;

/**
 * Class SynchronizableSequenceTest
 */
class SynchronizableSequenceTest extends \PHPUnit_Framework_TestCase
{
    public function testSyncForEqualSequences()
    {
        $seq1 = new Sequence([1, 2, 3, 4, 5]);
        $seq2 = new SynchronizableSequence([1, 2, 3, 4, 5], []);

        $lastIndex = $seq2->synchronize($seq1);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(4, $lastIndex);

        $this->assertEquals([], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([], $seq2->getRemovedElements());
        $this->assertEquals([1, 2, 3, 4, 5], $seq2->getEqualElements());
    }

    public function testSyncForRemovedElement()
    {
        $seq1 = new Sequence([1, 3, 4, 5]);
        $seq2 = new SynchronizableSequence([1, 2, 3, 4, 5], []);

        $lastIndex = $seq2->synchronize($seq1);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(3, $lastIndex);

        $this->assertEquals([], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([2], $seq2->getRemovedElements());
        $this->assertEquals([1, 3, 4, 5], $seq2->getEqualElements());
    }

    public function testSyncForAddedElement()
    {
        $seq1 = new Sequence([1, 2, 3, 4, 5, 6]);
        $seq2 = new SynchronizableSequence([1, 2, 3, 4, 5], []);

        $lastIndex = $seq2->synchronize($seq1);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(5, $lastIndex);

        $this->assertEquals([6], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([], $seq2->getRemovedElements());
        $this->assertEquals([1, 2, 3, 4, 5], $seq2->getEqualElements());
    }

    public function testSyncWithoutOutOfBoundCheck()
    {
        $seq1 = new Sequence([1, 4, 6]);
        $seq2 = new SynchronizableSequence([1, 2, 3, 4], []);

        $lastIndex = $seq2->synchronize($seq1);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(2, $lastIndex);

        $this->assertEquals([6], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([2, 3], $seq2->getRemovedElements());
        $this->assertEquals([1, 4], $seq2->getEqualElements());
    }

    public function testSyncWithCheckAndOutOfBoundElement()
    {
        $seq1 = new Sequence([1, 4, 6]);
        $seq2 = new SynchronizableSequence([1, 2, 3, 4], []);

        $lastIndex = $seq2->synchronize($seq1, true);

        $this->assertEquals([1, 4], $seq2->toArray());
        $this->assertEquals(1, $lastIndex);

        $this->assertEquals([], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([2, 3], $seq2->getRemovedElements());
        $this->assertEquals([1, 4], $seq2->getEqualElements());
    }

    public function testSyncWithoutCheckAndRemovalAtEnd()
    {
        $seq1 = new Sequence([1, 3, 4]);
        $seq2 = new SynchronizableSequence([1, 2, 4, 7], []);

        $lastIndex = $seq2->synchronize($seq1);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(2, $lastIndex);

        $this->assertEquals([3], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([2, 7], $seq2->getRemovedElements());
        $this->assertEquals([1, 4], $seq2->getEqualElements());
    }

    public function testSyncWithCheckAndRemovalAtEnd()
    {
        $seq1 = new Sequence([1, 3, 4]);
        $seq2 = new SynchronizableSequence([1, 2, 4, 7], []);

        $lastIndex = $seq2->synchronize($seq1, true);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(2, $lastIndex);

        $this->assertEquals([3], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([2, 7], $seq2->getRemovedElements());
        $this->assertEquals([1, 4], $seq2->getEqualElements());
    }

    public function testSyncWithCheckAndOutOfBoundAtEnd()
    {
        $seq1 = new Sequence([1, 3, 4]);
        $seq2 = new SynchronizableSequence([1, 5, 6], []);

        $lastIndex = $seq2->synchronize($seq1, true);

        $this->assertEquals([1, 3, 4, 5, 6], $seq2->toArray());
        $this->assertEquals(2, $lastIndex);

        $this->assertEquals([3, 4], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([], $seq2->getRemovedElements());
        $this->assertEquals([1], $seq2->getEqualElements());
    }

    public function testSyncWithCheckAndFullAddition()
    {
        $seq1 = new Sequence([1, 3, 4]);
        $seq2 = new SynchronizableSequence([], []);

        $lastIndex = $seq2->synchronize($seq1, true);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(2, $lastIndex);

        $this->assertEquals([1, 3, 4], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([], $seq2->getRemovedElements());
        $this->assertEquals([], $seq2->getEqualElements());
    }

    public function testSyncWithCheckAndFullRemoval()
    {
        $seq1 = new Sequence([]);
        $seq2 = new SynchronizableSequence([1, 3, 4], []);

        $lastIndex = $seq2->synchronize($seq1, true);

        $this->assertEquals($seq1->toArray(), $seq2->toArray());
        $this->assertEquals(-1, $lastIndex);

        $this->assertEquals([], $seq2->getAddedElements());
        $this->assertEquals([], $seq2->getUpdatedElements());
        $this->assertEquals([1, 3, 4], $seq2->getRemovedElements());
        $this->assertEquals([], $seq2->getEqualElements());
    }
}
