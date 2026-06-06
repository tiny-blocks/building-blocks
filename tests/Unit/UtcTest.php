<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit;

use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidUtc;
use TinyBlocks\BuildingBlocks\Utc;

final class UtcTest extends TestCase
{
    public function testEqualsWhenSamePointInTimeThenReturnsTrue(): void
    {
        /** @Given a moment in UTC */
        $utc = Utc::fromIso8601(value: '2026-02-17T10:30:00+00:00');

        /** @And another moment at the same point in time */
        $other = Utc::fromIso8601(value: '2026-02-17T10:30:00+00:00');

        /** @When comparing them */
        $areEqual = $utc->equals(other: $other);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsWhenDifferentPointInTimeThenReturnsFalse(): void
    {
        /** @Given a moment in UTC */
        $utc = Utc::fromIso8601(value: '2026-02-17T10:30:00+00:00');

        /** @And another moment at a different point in time */
        $other = Utc::fromIso8601(value: '2020-01-01T00:00:00+00:00');

        /** @When comparing them */
        $areEqual = $utc->equals(other: $other);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testNowThenProducesUtcThatRoundTripsThroughIso8601(): void
    {
        /** @When the current moment is created */
        $utc = Utc::now();

        /** @Then it round-trips through its ISO 8601 representation */
        self::assertTrue($utc->equals(other: Utc::fromIso8601(value: $utc->toIso8601())));
    }

    public function testFromIso8601WhenValueIsNotValidThenThrowsInvalidUtc(): void
    {
        /** @Then an exception indicating an invalid instant should be thrown */
        $this->expectException(InvalidUtc::class);
        $this->expectExceptionMessage('Value <not-a-valid-instant> is not a valid ISO 8601 instant.');

        /** @When creating a moment from a value that is not a valid date-time */
        Utc::fromIso8601(value: 'not-a-valid-instant');
    }

    public function testFromIso8601WhenGivenIso8601StringThenExposesSecondPrecision(): void
    {
        /** @Given an ISO 8601 date-time string without sub-second precision */
        $value = '2026-02-17T10:30:00+00:00';

        /** @When a moment is created from it and read back */
        $iso = Utc::fromIso8601(value: $value)->toIso8601();

        /** @Then it is exposed in UTC with second precision */
        self::assertSame('2026-02-17T10:30:00+00:00', $iso);
    }
}
