<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks;

use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Uuid as RamseyUuid;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidUuid;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

/**
 * A universally unique identifier, compared by its canonical value.
 */
final readonly class Uuid implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(private string $value)
    {
    }

    /**
     * Creates an Uuid from its canonical string representation.
     *
     * @param string $value The canonical string representation of a UUID.
     * @return Uuid The created identifier.
     * @throws InvalidUuid If the value is not a valid UUID.
     */
    public static function from(string $value): Uuid
    {
        try {
            return new Uuid(value: RamseyUuid::fromString(uuid: $value)->toString());
        } catch (InvalidArgumentException) {
            throw new InvalidUuid(value: $value);
        }
    }

    /**
     * Creates an Uuid from its canonical version 7 string representation.
     *
     * @param string $value The canonical string representation of a version 7 UUID.
     * @return Uuid The created identifier.
     * @throws InvalidUuid If the value is not a valid UUID or its version is not 7.
     */
    public static function fromV7(string $value): Uuid
    {
        try {
            $parsed = RamseyUuid::fromString(uuid: $value);
        } catch (InvalidArgumentException) {
            throw new InvalidUuid(value: $value);
        }

        if ($parsed->getVersion() !== 7) {
            throw new InvalidUuid(value: $value);
        }

        return new Uuid(value: $parsed->toString());
    }

    /**
     * Creates an Uuid generated as a version 7 (Unix Epoch time) identifier.
     *
     * @return Uuid The generated version 7 identifier.
     */
    public static function generateV7(): Uuid
    {
        return new Uuid(value: RamseyUuid::uuid7()->toString());
    }

    /**
     * Returns the Uuid as its canonical string representation.
     *
     * @return string The canonical string representation.
     */
    public function toString(): string
    {
        return $this->value;
    }
}
