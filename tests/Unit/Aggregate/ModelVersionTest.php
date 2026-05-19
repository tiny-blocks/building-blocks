<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Aggregate;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Aggregate\ModelVersion;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidModelVersion;

final class ModelVersionTest extends TestCase
{
    public function testInitialReturnsVersionZero(): void
    {
        /** @When requesting the initial model version */
        $version = ModelVersion::initial();

        /** @Then the value is zero */
        self::assertSame(0, $version->value);
    }

    public function testOfReturnsVersionWithGivenValue(): void
    {
        /** @When requesting a model version of that value */
        $version = ModelVersion::of(value: 2);

        /** @Then the value matches */
        self::assertSame(2, $version->value);
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        /** @Given two model versions with the same value */
        $first = ModelVersion::of(value: 3);

        /** @And a matching counterpart */
        $second = ModelVersion::of(value: 3);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        /** @Given two model versions with different values */
        $first = ModelVersion::of(value: 1);

        /** @And a distinct counterpart */
        $second = ModelVersion::of(value: 2);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testIsAfterReturnsTrueWhenValueIsGreater(): void
    {
        /** @Given a larger model version */
        $larger = ModelVersion::of(value: 5);

        /** @And a smaller counterpart */
        $smaller = ModelVersion::of(value: 2);

        /** @When checking if the larger is after the smaller */
        $isAfter = $larger->isAfter(other: $smaller);

        /** @Then the result is true */
        self::assertTrue($isAfter);
    }

    public function testIsAfterReturnsFalseWhenValuesAreEqual(): void
    {
        /** @Given two equal model versions */
        $first = ModelVersion::of(value: 4);

        /** @And a counterpart with the same value */
        $second = ModelVersion::of(value: 4);

        /** @When checking if one is strictly after the other */
        $isAfter = $first->isAfter(other: $second);

        /** @Then the result is false */
        self::assertFalse($isAfter);
    }

    public function testIsAfterReturnsFalseWhenValueIsLess(): void
    {
        /** @Given a smaller model version */
        $smaller = ModelVersion::of(value: 1);

        /** @And a larger counterpart */
        $larger = ModelVersion::of(value: 8);

        /** @When checking if the smaller is after the larger */
        $isAfter = $smaller->isAfter(other: $larger);

        /** @Then the result is false */
        self::assertFalse($isAfter);
    }

    public function testIsBeforeReturnsTrueWhenValueIsLess(): void
    {
        /** @Given a smaller model version */
        $smaller = ModelVersion::of(value: 3);

        /** @And a larger counterpart */
        $larger = ModelVersion::of(value: 7);

        /** @When checking if the smaller is before the larger */
        $isBefore = $smaller->isBefore(other: $larger);

        /** @Then the result is true */
        self::assertTrue($isBefore);
    }

    public function testIsBeforeReturnsFalseWhenValuesAreEqual(): void
    {
        /** @Given two equal model versions */
        $first = ModelVersion::of(value: 9);

        /** @And a counterpart with the same value */
        $second = ModelVersion::of(value: 9);

        /** @When checking if one is strictly before the other */
        $isBefore = $first->isBefore(other: $second);

        /** @Then the result is false */
        self::assertFalse($isBefore);
    }

    public function testIsBeforeReturnsFalseWhenValueIsGreater(): void
    {
        /** @Given a larger model version */
        $larger = ModelVersion::of(value: 10);

        /** @And a smaller counterpart */
        $smaller = ModelVersion::of(value: 2);

        /** @When checking if the larger is before the smaller */
        $isBefore = $larger->isBefore(other: $smaller);

        /** @Then the result is false */
        self::assertFalse($isBefore);
    }

    public function testOfRejectsNegativeValue(): void
    {
        /** @Then an InvalidModelVersion exception is thrown */
        $this->expectException(InvalidModelVersion::class);
        $this->expectExceptionMessage('-1');

        /** @When constructing with a negative value */
        ModelVersion::of(value: -1);
    }

    public function testInvalidModelVersionIsCatchableAsInvalidArgumentException(): void
    {
        /** @Then InvalidModelVersion is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with a negative value */
        ModelVersion::of(value: -1);
    }

    public function testInvalidModelVersionMessageMentionsTheMinimumAllowed(): void
    {
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidModelVersion::class);
        $this->expectExceptionMessage('greater than or equal to 0');

        /** @When constructing with a negative value */
        ModelVersion::of(value: -1);
    }
}
