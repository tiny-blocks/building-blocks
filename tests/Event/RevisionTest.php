<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidRevision;

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
        /** @Given the initial revision factory */
        /** @When requesting the initial revision */
        $revision = Revision::initial();

        /** @Then the value is one */
        self::assertSame(1, $revision->value);
    }

    public function testOfReturnsRevisionWithGivenValue(): void
    {
        /** @Given a valid revision value */
        /** @When requesting a revision of that value */
        $revision = Revision::of(value: 42);

        /** @Then the value matches */
        self::assertSame(42, $revision->value);
    }

    public function testOfStoresTheMinimumValidValue(): void
    {
        /** @Given the minimum valid revision value */
        /** @When constructing the revision via factory */
        $revision = Revision::of(value: 1);

        /** @Then the value is stored verbatim */
        self::assertSame(1, $revision->value);
    }

    public function testEqualsReturnsTrueForSameRevision(): void
    {
        /** @Given two revisions with the same value */
        $first = Revision::of(value: 2);

        /** @And a matching counterpart */
        $second = Revision::of(value: 2);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentRevisions(): void
    {
        /** @Given two revisions with different values */
        $first = Revision::of(value: 1);

        /** @And a distinct counterpart */
        $second = Revision::of(value: 2);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($result);
    }

    #[DataProvider('invalidValues')]
    public function testOfRejectsNonPositiveValue(int $invalidValue): void
    {
        /** @Given a value that violates the revision invariant */
        /** @Then an InvalidRevision exception carrying the invalid value is thrown */
        $this->expectException(InvalidRevision::class);
        $this->expectExceptionMessage((string) $invalidValue);

        /** @When constructing with a non-positive value */
        Revision::of(value: $invalidValue);
    }

    public function testInvalidRevisionIsCatchableAsInvalidArgumentException(): void
    {
        /** @Given consumer code catching the PHP-standard InvalidArgumentException */
        /** @Then InvalidRevision is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with an invalid value */
        Revision::of(value: 0);
    }

    public function testInvalidRevisionMessageMentionsTheMinimumAllowed(): void
    {
        /** @Given a consumer inspecting the exception message */
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidRevision::class);
        $this->expectExceptionMessage('greater than or equal to 1');

        /** @When constructing with an invalid value */
        Revision::of(value: 0);
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
