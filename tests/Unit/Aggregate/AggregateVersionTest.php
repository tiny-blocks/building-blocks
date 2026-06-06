<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Aggregate;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidAggregateVersion;

final class AggregateVersionTest extends TestCase
{
    public function testConstructorIsPrivate(): void
    {
        /** @Given the AggregateVersion class constructor */
        $constructor = new ReflectionMethod(AggregateVersion::class, '__construct');

        /** @When inspecting its visibility */
        /** @Then the constructor is private */
        self::assertTrue($constructor->isPrivate());
    }

    public function testInitialYieldsZero(): void
    {
        /** @When requesting the initial aggregate version */
        $aggregateVersion = AggregateVersion::initial();

        /** @Then the value is zero */
        self::assertSame(0, $aggregateVersion->value);
    }

    public function testFirstYieldsOne(): void
    {
        /** @When requesting the first aggregate version */
        $aggregateVersion = AggregateVersion::first();

        /** @Then the value is one */
        self::assertSame(1, $aggregateVersion->value);
    }

    public function testOfReturnsAggregateVersionWithGivenValue(): void
    {
        /** @When requesting an aggregate version of that value */
        $aggregateVersion = AggregateVersion::of(value: 5);

        /** @Then the value matches */
        self::assertSame(5, $aggregateVersion->value);
    }

    public function testNextYieldsTheFollowingValue(): void
    {
        /** @Given an aggregate version of 5 */
        $aggregateVersion = AggregateVersion::of(value: 5);

        /** @When advancing to the next */
        $next = $aggregateVersion->next();

        /** @Then the value is 6 */
        self::assertSame(6, $next->value);
    }

    public function testNextDoesNotMutateTheSource(): void
    {
        /** @Given an aggregate version of 5 */
        $aggregateVersion = AggregateVersion::of(value: 5);

        /** @When advancing */
        $aggregateVersion->next();

        /** @Then the original is unchanged */
        self::assertSame(5, $aggregateVersion->value);
    }

    public function testValueReturnsTheBackingInteger(): void
    {
        /** @Given an aggregate version of 5 */
        $aggregateVersion = AggregateVersion::of(value: 5);

        /** @When retrieving its ordinal value */
        $value = $aggregateVersion->value();

        /** @Then the backing integer is returned */
        self::assertSame(5, $value);
    }

    public function testIsAfterReturnsTrueWhenStrictlyGreater(): void
    {
        /** @Given a larger aggregate version */
        $larger = AggregateVersion::of(value: 10);

        /** @And a smaller counterpart */
        $smaller = AggregateVersion::of(value: 5);

        /** @When checking if the larger is after the smaller */
        $isAfter = $larger->isAfter(other: $smaller);

        /** @Then the result is true */
        self::assertTrue($isAfter);
    }

    public function testIsAfterReturnsFalseWhenEqual(): void
    {
        /** @Given two equal aggregate versions */
        $first = AggregateVersion::of(value: 3);

        /** @And a counterpart with the same value */
        $second = AggregateVersion::of(value: 3);

        /** @When checking if one is strictly after the other */
        $isAfter = $first->isAfter(other: $second);

        /** @Then the result is false */
        self::assertFalse($isAfter);
    }

    public function testIsAfterReturnsFalseWhenStrictlySmaller(): void
    {
        /** @Given a smaller aggregate version */
        $smaller = AggregateVersion::of(value: 2);

        /** @And a larger counterpart */
        $larger = AggregateVersion::of(value: 8);

        /** @When checking if the smaller is after the larger */
        $isAfter = $smaller->isAfter(other: $larger);

        /** @Then the result is false */
        self::assertFalse($isAfter);
    }

    public function testIsBeforeReturnsTrueWhenValueIsLess(): void
    {
        /** @Given a smaller aggregate version */
        $smaller = AggregateVersion::of(value: 4);

        /** @And a larger counterpart */
        $larger = AggregateVersion::of(value: 9);

        /** @When checking if the smaller is before the larger */
        $isBefore = $smaller->isBefore(other: $larger);

        /** @Then the result is true */
        self::assertTrue($isBefore);
    }

    public function testIsBeforeReturnsFalseWhenValuesAreEqual(): void
    {
        /** @Given two equal aggregate versions */
        $first = AggregateVersion::of(value: 6);

        /** @And a counterpart with the same value */
        $second = AggregateVersion::of(value: 6);

        /** @When checking if one is strictly before the other */
        $isBefore = $first->isBefore(other: $second);

        /** @Then the result is false */
        self::assertFalse($isBefore);
    }

    public function testIsBeforeReturnsFalseWhenValueIsGreater(): void
    {
        /** @Given a larger aggregate version */
        $larger = AggregateVersion::of(value: 12);

        /** @And a smaller counterpart */
        $smaller = AggregateVersion::of(value: 3);

        /** @When checking if the larger is before the smaller */
        $isBefore = $larger->isBefore(other: $smaller);

        /** @Then the result is false */
        self::assertFalse($isBefore);
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        /** @Given two aggregate versions with the same value */
        $first = AggregateVersion::of(value: 7);

        /** @And a matching counterpart */
        $second = AggregateVersion::of(value: 7);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        /** @Given two aggregate versions with different values */
        $first = AggregateVersion::of(value: 1);

        /** @And a distinct counterpart */
        $second = AggregateVersion::of(value: 2);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    #[DataProvider('negativeValues')]
    public function testOfRejectsNegativeValue(int $negativeValue): void
    {
        /** @Then an InvalidAggregateVersion exception carrying the invalid value is thrown */
        $this->expectException(InvalidAggregateVersion::class);
        $this->expectExceptionMessage((string)$negativeValue);

        /** @When constructing with a negative value */
        AggregateVersion::of(value: $negativeValue);
    }

    public function testInvalidAggregateVersionIsCatchableAsInvalidArgumentException(): void
    {
        /** @Then InvalidAggregateVersion is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with a negative value */
        AggregateVersion::of(value: -1);
    }

    public function testInvalidAggregateVersionMessageMentionsTheMinimumAllowed(): void
    {
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidAggregateVersion::class);
        $this->expectExceptionMessage('greater than or equal to 0');

        /** @When constructing with a negative value */
        AggregateVersion::of(value: -1);
    }

    public static function negativeValues(): array
    {
        return [
            'minus one' => [-1],
            'minus ten' => [-10]
        ];
    }
}
