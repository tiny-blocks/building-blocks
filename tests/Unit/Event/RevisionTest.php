<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidRevision;

final class RevisionTest extends TestCase
{
    public function testConstructorIsPrivate(): void
    {
        /** @Given the Revision class constructor */
        $constructor = new ReflectionMethod(Revision::class, '__construct');

        /** @When inspecting its visibility */
        /** @Then the constructor is private */
        self::assertTrue($constructor->isPrivate());
    }

    public function testInitialReturnsRevisionOfOne(): void
    {
        /** @When requesting the initial revision */
        $revision = Revision::initial();

        /** @Then the value is one */
        self::assertSame(1, $revision->value);
    }

    public function testOfReturnsRevisionWithGivenValue(): void
    {
        /** @When requesting a revision of that value */
        $revision = Revision::of(value: 42);

        /** @Then the value matches */
        self::assertSame(42, $revision->value);
    }

    public function testOfStoresTheMinimumValidValue(): void
    {
        /** @When constructing the revision via factory */
        $revision = Revision::of(value: 1);

        /** @Then the value is stored verbatim */
        self::assertSame(1, $revision->value);
    }

    public function testValueReturnsTheBackingInteger(): void
    {
        /** @Given a revision of 42 */
        $revision = Revision::of(value: 42);

        /** @When retrieving its ordinal value */
        $value = $revision->value();

        /** @Then the backing integer is returned */
        self::assertSame(42, $value);
    }

    public function testEqualsReturnsTrueForSameRevision(): void
    {
        /** @Given two revisions with the same value */
        $first = Revision::of(value: 2);

        /** @And a matching counterpart */
        $second = Revision::of(value: 2);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForDifferentRevisions(): void
    {
        /** @Given two revisions with different values */
        $first = Revision::of(value: 1);

        /** @And a distinct counterpart */
        $second = Revision::of(value: 2);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testIsAfterReturnsTrueWhenValueIsGreater(): void
    {
        /** @Given a revision with a higher value */
        $higher = Revision::of(value: 3);

        /** @And a revision with a lower value */
        $lower = Revision::of(value: 1);

        /** @When checking if higher is after lower */
        $isAfter = $higher->isAfter(other: $lower);

        /** @Then the result is true */
        self::assertTrue($isAfter);
    }

    public function testIsAfterReturnsFalseWhenValueIsEqual(): void
    {
        /** @Given two revisions with equal values */
        $first = Revision::of(value: 2);

        /** @And a matching counterpart */
        $second = Revision::of(value: 2);

        /** @When checking if first is after second */
        $isAfter = $first->isAfter(other: $second);

        /** @Then the result is false */
        self::assertFalse($isAfter);
    }

    public function testIsAfterReturnsFalseWhenValueIsLower(): void
    {
        /** @Given a revision with a lower value */
        $lower = Revision::of(value: 1);

        /** @And a revision with a higher value */
        $higher = Revision::of(value: 3);

        /** @When checking if lower is after higher */
        $isAfter = $lower->isAfter(other: $higher);

        /** @Then the result is false */
        self::assertFalse($isAfter);
    }

    public function testIsBeforeReturnsTrueWhenValueIsLower(): void
    {
        /** @Given a revision with a lower value */
        $lower = Revision::of(value: 1);

        /** @And a revision with a higher value */
        $higher = Revision::of(value: 3);

        /** @When checking if lower is before higher */
        $isBefore = $lower->isBefore(other: $higher);

        /** @Then the result is true */
        self::assertTrue($isBefore);
    }

    public function testIsBeforeReturnsFalseWhenValueIsEqual(): void
    {
        /** @Given two revisions with equal values */
        $first = Revision::of(value: 2);

        /** @And a matching counterpart */
        $second = Revision::of(value: 2);

        /** @When checking if first is before second */
        $isBefore = $first->isBefore(other: $second);

        /** @Then the result is false */
        self::assertFalse($isBefore);
    }

    public function testIsBeforeReturnsFalseWhenValueIsGreater(): void
    {
        /** @Given a revision with a higher value */
        $higher = Revision::of(value: 3);

        /** @And a revision with a lower value */
        $lower = Revision::of(value: 1);

        /** @When checking if higher is before lower */
        $isBefore = $higher->isBefore(other: $lower);

        /** @Then the result is false */
        self::assertFalse($isBefore);
    }

    #[DataProvider('invalidValues')]
    public function testOfRejectsNonPositiveValue(int $invalidValue): void
    {
        /** @Then an InvalidRevision exception carrying the invalid value is thrown */
        $this->expectException(InvalidRevision::class);
        $this->expectExceptionMessage((string)$invalidValue);

        /** @When constructing with a non-positive value */
        Revision::of(value: $invalidValue);
    }

    public function testInvalidRevisionIsCatchableAsInvalidArgumentException(): void
    {
        /** @Then InvalidRevision is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with an invalid value */
        Revision::of(value: 0);
    }

    public function testInvalidRevisionMessageMentionsTheMinimumAllowed(): void
    {
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidRevision::class);
        $this->expectExceptionMessage('greater than or equal to 1');

        /** @When constructing with an invalid value */
        Revision::of(value: 0);
    }

    public static function invalidValues(): array
    {
        return [
            'zero'         => [0],
            'negative one' => [-1],
            'negative ten' => [-10]
        ];
    }
}
