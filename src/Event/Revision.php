<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Exceptions\InvalidRevision;
use TinyBlocks\BuildingBlocks\Ordinal;
use TinyBlocks\BuildingBlocks\OrdinalBehavior;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Revision implements ValueObject, Ordinal
{
    use ValueObjectBehavior;
    use OrdinalBehavior;

    private function __construct(public int $value)
    {
        if ($value < 1) {
            throw new InvalidRevision(value: $value);
        }
    }

    /**
     * Creates a Revision from the given integer value.
     *
     * @param int $value The revision number. Must be greater than or equal to 1.
     * @return Revision The created instance.
     * @throws InvalidRevision If the value is less than 1.
     */
    public static function of(int $value): Revision
    {
        return new Revision(value: $value);
    }

    /**
     * Creates a Revision with the initial value of 1.
     *
     * @return Revision The initial revision.
     */
    public static function initial(): Revision
    {
        return new Revision(value: 1);
    }

    public function value(): int
    {
        return $this->value;
    }
}
