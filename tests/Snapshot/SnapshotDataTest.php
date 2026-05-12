<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotData;

final class SnapshotDataTest extends TestCase
{
    public function testToArrayReturnsTheOriginalPayload(): void
    {
        /** @Given snapshot data with a payload */
        $snapshotData = new SnapshotData(payload: ['status' => 'placed', 'amount' => 100]);

        /** @When converting to array */
        $payload = $snapshotData->toArray();

        /** @Then the original data is returned */
        self::assertSame(['status' => 'placed', 'amount' => 100], $payload);
    }

    public function testToArrayReturnsSameReferenceForNestedPayload(): void
    {
        /** @Given snapshot data with a nested payload */
        $snapshotData = new SnapshotData(payload: ['order' => ['item' => 'book', 'qty' => 2]]);

        /** @When converting to array */
        $payload = $snapshotData->toArray();

        /** @Then the nested structure is preserved exactly */
        self::assertSame(['order' => ['item' => 'book', 'qty' => 2]], $payload);
    }

    public function testEqualsReturnsTrueForIdenticalPayloads(): void
    {
        /** @Given two snapshot data instances with identical payloads */
        $first = new SnapshotData(payload: ['status' => 'placed']);

        /** @And a matching counterpart */
        $second = new SnapshotData(payload: ['status' => 'placed']);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForDifferentPayloads(): void
    {
        /** @Given two snapshot data instances with different payloads */
        $first = new SnapshotData(payload: ['status' => 'placed']);

        /** @And a distinct counterpart */
        $second = new SnapshotData(payload: ['status' => 'shipped']);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }
}
