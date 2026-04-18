<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidSequenceNumber;

final class SequenceNumberTest extends TestCase
{
    public function testInitialYieldsZero(): void
    {
        /** @Given the initial-sequence factory */
        /** @When requesting the initial sequence number */
        $sequenceNumber = SequenceNumber::initial();

        /** @Then the value is zero */
        self::assertSame(0, $sequenceNumber->value);
    }

    public function testFirstYieldsOne(): void
    {
        /** @Given the first-sequence factory */
        /** @When requesting the first sequence number */
        $sequenceNumber = SequenceNumber::first();

        /** @Then the value is one */
        self::assertSame(1, $sequenceNumber->value);
    }

    public function testNextYieldsTheFollowingValue(): void
    {
        /** @Given a sequence number of 5 */
        $sequenceNumber = new SequenceNumber(value: 5);

        /** @When advancing to the next */
        $next = $sequenceNumber->next();

        /** @Then the value is 6 */
        self::assertSame(6, $next->value);
    }

    public function testNextDoesNotMutateTheSource(): void
    {
        /** @Given a sequence number of 5 */
        $sequenceNumber = new SequenceNumber(value: 5);

        /** @When advancing */
        $sequenceNumber->next();

        /** @Then the original is unchanged */
        self::assertSame(5, $sequenceNumber->value);
    }

    public function testIsAfterReturnsTrueWhenStrictlyGreater(): void
    {
        /** @Given a larger sequence number */
        $larger = new SequenceNumber(value: 10);

        /** @And a smaller counterpart */
        $smaller = new SequenceNumber(value: 5);

        /** @When checking if the larger is after the smaller */
        $result = $larger->isAfter(other: $smaller);

        /** @Then the result is true */
        self::assertTrue($result);
    }

    public function testIsAfterReturnsFalseWhenEqual(): void
    {
        /** @Given two equal sequence numbers */
        $first = new SequenceNumber(value: 3);

        /** @And a counterpart with the same value */
        $second = new SequenceNumber(value: 3);

        /** @When checking if one is strictly after the other */
        $result = $first->isAfter(other: $second);

        /** @Then the result is false */
        self::assertFalse($result);
    }

    public function testIsAfterReturnsFalseWhenStrictlySmaller(): void
    {
        /** @Given a smaller sequence number */
        $smaller = new SequenceNumber(value: 2);

        /** @And a larger counterpart */
        $larger = new SequenceNumber(value: 8);

        /** @When checking if the smaller is after the larger */
        $result = $smaller->isAfter(other: $larger);

        /** @Then the result is false */
        self::assertFalse($result);
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        /** @Given two sequence numbers with the same value */
        $first = new SequenceNumber(value: 7);

        /** @And a matching counterpart */
        $second = new SequenceNumber(value: 7);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        /** @Given two sequence numbers with different values */
        $first = new SequenceNumber(value: 1);

        /** @And a distinct counterpart */
        $second = new SequenceNumber(value: 2);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($result);
    }

    #[DataProvider('negativeValues')]
    public function testConstructorRejectsNegativeValue(int $negativeValue): void
    {
        /** @Given a value that violates the sequence-number invariant */
        /** @Then an InvalidSequenceNumber exception carrying the invalid value is thrown */
        $this->expectException(InvalidSequenceNumber::class);
        $this->expectExceptionMessage((string) $negativeValue);

        /** @When constructing with a negative value */
        new SequenceNumber(value: $negativeValue);
    }

    public function testInvalidSequenceNumberIsCatchableAsInvalidArgumentException(): void
    {
        /** @Given consumer code catching the PHP-standard InvalidArgumentException */
        /** @Then InvalidSequenceNumber is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with a negative value */
        new SequenceNumber(value: -1);
    }

    public function testInvalidSequenceNumberMessageMentionsTheMinimumAllowed(): void
    {
        /** @Given a consumer inspecting the exception message */
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidSequenceNumber::class);
        $this->expectExceptionMessage('greater than or equal to 0');

        /** @When constructing with a negative value */
        new SequenceNumber(value: -1);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function negativeValues(): array
    {
        return [
            'minus one' => [-1],
            'minus ten' => [-10]
        ];
    }
}
