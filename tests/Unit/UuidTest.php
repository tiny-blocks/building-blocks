<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit;

use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid as RamseyUuid;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidUuid;
use TinyBlocks\BuildingBlocks\Uuid;

final class UuidTest extends TestCase
{
    private const string IDENTIFIER = '019e8ba3-5dd3-70c3-8f43-f3dd0224517d';
    private const string OTHER_IDENTIFIER = 'b10766b6-5f00-11f1-92d5-641c67863e22';
    private const string VERSION_4_IDENTIFIER = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    public function testEqualsWhenSameIdentifierThenReturnsTrue(): void
    {
        /** @Given an identifier */
        $uuid = Uuid::from(value: self::IDENTIFIER);

        /** @And another identifier holding the same value */
        $other = Uuid::from(value: self::IDENTIFIER);

        /** @When comparing them */
        $areEqual = $uuid->equals(other: $other);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testGenerateV7ThenProducesVersion7Identifier(): void
    {
        /** @When a new identifier is generated */
        $uuid = Uuid::generateV7();

        /** @Then its version is 7 */
        self::assertSame(7, RamseyUuid::fromString(uuid: $uuid->toString())->getVersion());
    }

    public function testFromThenExposesCanonicalStringRepresentation(): void
    {
        /** @Given a canonical UUID string */
        $value = self::IDENTIFIER;

        /** @When an identifier is created from it */
        $uuid = Uuid::from(value: $value);

        /** @Then it exposes the same canonical string representation */
        self::assertSame($value, $uuid->toString());
    }

    public function testFromWhenValueIsNotValidThenThrowsInvalidUuid(): void
    {
        /** @Then an exception indicating an invalid UUID should be thrown */
        $this->expectException(InvalidUuid::class);
        $this->expectExceptionMessage('Value <not-a-valid-uuid> is not a valid UUID.');

        /** @When creating an identifier from a value that is not a valid UUID */
        Uuid::from(value: 'not-a-valid-uuid');
    }

    public function testEqualsWhenDifferentIdentifierThenReturnsFalse(): void
    {
        /** @Given an identifier */
        $uuid = Uuid::from(value: self::IDENTIFIER);

        /** @And another identifier holding a different value */
        $other = Uuid::from(value: self::OTHER_IDENTIFIER);

        /** @When comparing them */
        $areEqual = $uuid->equals(other: $other);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testFromV7WhenValueIsNotValidThenThrowsInvalidUuid(): void
    {
        /** @Then an exception indicating an invalid UUID should be thrown */
        $this->expectException(InvalidUuid::class);
        $this->expectExceptionMessage('Value <not-a-valid-uuid> is not a valid UUID.');

        /** @When creating a version 7 identifier from a value that is not a valid UUID */
        Uuid::fromV7(value: 'not-a-valid-uuid');
    }

    public function testFromV7WhenVersionIsNotSevenThenThrowsInvalidUuid(): void
    {
        /** @Then an exception indicating an invalid UUID should be thrown */
        $this->expectException(InvalidUuid::class);
        $this->expectExceptionMessage('Value <f47ac10b-58cc-4372-a567-0e02b2c3d479> is not a valid UUID.');

        /** @When creating a version 7 identifier from a valid UUID whose version is not 7 */
        Uuid::fromV7(value: self::VERSION_4_IDENTIFIER);
    }

    public function testFromV7WhenValueIsVersion7ThenExposesCanonicalStringRepresentation(): void
    {
        /** @Given a canonical version 7 UUID string */
        $value = self::IDENTIFIER;

        /** @When a version 7 identifier is created from it */
        $uuid = Uuid::fromV7(value: $value);

        /** @Then it exposes the same canonical string representation */
        self::assertSame($value, $uuid->toString());
    }
}
