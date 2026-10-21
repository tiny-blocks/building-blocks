<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\Vo\ValueObject;

/**
 * Immutable value that uniquely identifies an {@see Entity} within its aggregate boundary.
 *
 * <p>Identity is the stable thread that allows an Entity to be recognized across distinct representations
 * and lifecycle states. Implementations are expected to be immutable, typically declared as a
 * <code>final readonly class</code>.</p>
 *
 * <p>The interface extends <code>ValueObject</code>, so identities compare by value. See the two shipped
 * variants: {@see SingleIdentity} for scalar-backed identifiers and {@see CompoundIdentity} for
 * multi-field tuples.</p>
 *
 * @see Eric Evans, <em>Domain-Driven Design: Tackling Complexity in the Heart of Software</em>
 *      (Addison-Wesley, 2003), Chapter 5 "Entities".
 */
interface Identity extends ValueObject
{
    /**
     * Returns the raw value of this identity.
     *
     * @return mixed A scalar value for single-field identities, an associative array for composite ones.
     */
    public function identityValue(): mixed;
}
