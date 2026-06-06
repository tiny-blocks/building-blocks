<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks;

/**
 * A value ordered by a single backing integer.
 *
 * <p>Implementations expose their position through {@see Ordinal::value()}, which {@see OrdinalBehavior}
 * uses to provide the <code>isAfter</code> and <code>isBefore</code> comparisons.</p>
 */
interface Ordinal
{
    /**
     * Returns the integer that backs the ordering.
     *
     * @return int The ordinal value.
     */
    public function value(): int;
}
