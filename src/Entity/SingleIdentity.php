<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\Vo\ValueObject;

/**
 * {@see Identity} composed of a single scalar value.
 *
 * <p>Pragmatic extension for the common case of identifiers backed by a single field (UUID string,
 * auto-increment integer, slug, etc.). Not a concept from Evans: the book makes no distinction between
 * single-value and composite identities.</p>
 *
 * <p>Implementations should declare exactly one property holding the scalar value; the default trait
 * reads it by reflection and returns it from <code>getIdentityValue()</code>.</p>
 */
interface SingleIdentity extends Identity, ValueObject
{
}
