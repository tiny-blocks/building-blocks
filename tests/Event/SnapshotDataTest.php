<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use JsonException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Event\SnapshotData;

final class SnapshotDataTest extends TestCase
{
    public function testToArrayReturnsTheOriginalPayload(): void
    {
        /** @Given snapshot data with a payload */
        $snapshotData = new SnapshotData(data: ['status' => 'placed', 'amount' => 100]);

        /** @When converting to array */
        $result = $snapshotData->toArray();

        /** @Then the original data is returned */
        self::assertSame(['status' => 'placed', 'amount' => 100], $result);
    }

    public function testToJsonProducesValidJson(): void
    {
        /** @Given snapshot data with a simple payload */
        $snapshotData = new SnapshotData(data: ['status' => 'shipped']);

        /** @When converting to JSON */
        $json = $snapshotData->toJson();

        /** @Then the result is valid JSON */
        self::assertSame('{"status":"shipped"}', $json);
    }

    public function testToJsonPreservesZeroFractionOnFloats(): void
    {
        /** @Given snapshot data with a float value */
        $snapshotData = new SnapshotData(data: ['amount' => 1.0]);

        /** @When converting to JSON with default flags */
        $json = $snapshotData->toJson();

        /** @Then the float zero fraction is preserved */
        self::assertSame('{"amount":1.0}', $json);
    }

    public function testToJsonHonorsAdditionalFlags(): void
    {
        /** @Given snapshot data with a nested payload */
        $snapshotData = new SnapshotData(data: ['amount' => 1.0]);

        /** @When converting to JSON with an additional pretty-print flag */
        $json = $snapshotData->toJson(flags: JSON_PRESERVE_ZERO_FRACTION | JSON_PRETTY_PRINT);

        /** @Then the output reflects the requested formatting */
        self::assertStringContainsString("\n", $json);
        self::assertStringContainsString('"amount": 1.0', $json);
    }

    public function testToJsonThrowsForNonSerializableValue(): void
    {
        /** @Given snapshot data containing a non-JSON-serializable value */
        $snapshotData = new SnapshotData(data: ['infinity' => INF]);

        /** @Then a JsonException is thrown */
        $this->expectException(JsonException::class);

        /** @When converting to JSON */
        $snapshotData->toJson();
    }

    public function testEqualsReturnsTrueForIdenticalPayloads(): void
    {
        /** @Given two snapshot data instances with identical payloads */
        $first = new SnapshotData(data: ['status' => 'placed']);

        /** @And a matching counterpart */
        $second = new SnapshotData(data: ['status' => 'placed']);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentPayloads(): void
    {
        /** @Given two snapshot data instances with different payloads */
        $first = new SnapshotData(data: ['status' => 'placed']);

        /** @And a distinct counterpart */
        $second = new SnapshotData(data: ['status' => 'shipped']);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($result);
    }
}
