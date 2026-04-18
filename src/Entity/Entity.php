<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityProperty;

/**
 * Object whose identity persists through time and changes of state.
 *
 * <p>An entity is distinguished not by its attributes but by a thread of identity that remains stable
 * across distinct representations and lifecycle transitions. Two entities are equal when their
 * identities are equal, regardless of attribute differences.</p>
 *
 * <p>Concrete entities implement the protected <code>identityName()</code> method returning the property
 * that holds their {@see Identity}. The default behavior uses reflection to resolve and compare it.</p>
 *
 * @see Eric Evans, <em>Domain-Driven Design: Tackling Complexity in the Heart of Software</em>
 *      (Addison-Wesley, 2003), Chapter 5 "Entities (a.k.a. Reference Objects)".
 */
interface Entity
{
    /**
     * Returns the Identity that uniquely identifies this entity.
     *
     * @return Identity The identity instance held by this entity.
     * @throws MissingIdentityProperty When the property referenced by <code>identityName()</code> does not exist.
     */
    public function getIdentity(): Identity;

    /**
     * Returns the name of the property that holds this entity's Identity.
     *
     * @return string The property name, resolved from <code>identityName()</code>.
     * @throws MissingIdentityProperty When the property referenced by <code>identityName()</code> does not exist.
     */
    public function getIdentityName(): string;

    /**
     * Returns the raw value of this entity's identity.
     *
     * <p>The shape of the returned value depends on the kind of identity held: a scalar for
     * {@see SingleIdentity}, an associative array for {@see CompoundIdentity}.</p>
     *
     * @return mixed The raw identity value.
     */
    public function getIdentityValue(): mixed;

    /**
     * Checks whether this entity and the given one share the same identity.
     *
     * @param Entity $other The entity whose identity will be compared.
     * @return bool True when both entities hold equal identities.
     */
    public function sameIdentityOf(Entity $other): bool;

    /**
     * Checks whether the given Identity is equal to this entity's identity.
     *
     * @param Identity $other The identity to compare against.
     * @return bool True when the given identity equals this entity's identity.
     */
    public function identityEquals(Identity $other): bool;
}
