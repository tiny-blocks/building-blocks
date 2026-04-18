<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidRevision;

final class RevisionTest extends TestCase
{
    public function testRevisionStoresTheMinimumValidValue(): void
    {
        /** @Given the minimum valid revision value */
        /** @When constructing the revision */
        $revision = new Revision(value: 1);

        /** @Then the value is stored verbatim */
        self::assertSame(1, $revision->value);
    }

    public function testRevisionStoresAHigherValidValue(): void
    {
        /** @Given a larger valid revision value */
        /** @When constructing the revision */
        $revision = new Revision(value: 42);

        /** @Then the value is stored verbatim */
        self::assertSame(42, $revision->value);
    }

    public function testEqualsReturnsTrueForSameRevision(): void
    {
        /** @Given two revisions with the same value */
        $first = new Revision(value: 2);

        /** @And a matching counterpart */
        $second = new Revision(value: 2);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentRevisions(): void
    {
        /** @Given two revisions with different values */
        $first = new Revision(value: 1);

        /** @And a distinct counterpart */
        $second = new Revision(value: 2);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($result);
    }

    #[DataProvider('invalidValues')]
    public function testConstructorRejectsNonPositiveValue(int $invalidValue): void
    {
        /** @Given a value that violates the revision invariant */
        /** @Then an InvalidRevision exception carrying the invalid value is thrown */
        $this->expectException(InvalidRevision::class);
        $this->expectExceptionMessage((string) $invalidValue);

        /** @When constructing with a non-positive value */
        new Revision(value: $invalidValue);
    }

    public function testInvalidRevisionIsCatchableAsInvalidArgumentException(): void
    {
        /** @Given consumer code catching the PHP-standard InvalidArgumentException */
        /** @Then InvalidRevision is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with an invalid value */
        new Revision(value: 0);
    }

    public function testInvalidRevisionMessageMentionsTheMinimumAllowed(): void
    {
        /** @Given a consumer inspecting the exception message */
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidRevision::class);
        $this->expectExceptionMessage('greater than or equal to 1');

        /** @When constructing with an invalid value */
        new Revision(value: 0);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function invalidValues(): array
    {
        return [
            'zero'         => [0],
            'negative one' => [-1],
            'negative ten' => [-10]
        ];
    }
}
