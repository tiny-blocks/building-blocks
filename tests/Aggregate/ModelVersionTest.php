<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Aggregate;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Aggregate\ModelVersion;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidModelVersion;

final class ModelVersionTest extends TestCase
{
    public function testInitialReturnsVersionZero(): void
    {
        /** @Given the initial model version factory */
        /** @When requesting the initial model version */
        $version = ModelVersion::initial();

        /** @Then the value is zero */
        self::assertSame(0, $version->value);
    }

    public function testOfReturnsVersionWithGivenValue(): void
    {
        /** @Given a valid model version value */
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

    public function testOfRejectsNegativeValue(): void
    {
        /** @Given a negative model version value */
        /** @Then an InvalidModelVersion exception is thrown */
        $this->expectException(InvalidModelVersion::class);
        $this->expectExceptionMessage('-1');

        /** @When constructing with a negative value */
        ModelVersion::of(value: -1);
    }

    public function testInvalidModelVersionIsCatchableAsInvalidArgumentException(): void
    {
        /** @Given consumer code catching the PHP-standard InvalidArgumentException */
        /** @Then InvalidModelVersion is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with a negative value */
        ModelVersion::of(value: -1);
    }

    public function testInvalidModelVersionMessageMentionsTheMinimumAllowed(): void
    {
        /** @Given a consumer inspecting the exception message */
        /** @Then the message mentions the minimum allowed value */
        $this->expectException(InvalidModelVersion::class);
        $this->expectExceptionMessage('greater than or equal to 0');

        /** @When constructing with a negative value */
        ModelVersion::of(value: -1);
    }
}
