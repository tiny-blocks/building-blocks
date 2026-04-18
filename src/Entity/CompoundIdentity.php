<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\Vo\ValueObject;

/**
 * {@see Identity} composed of multiple fields treated as a tuple.
 *
 * <p>Pragmatic extension for the common case of identifiers that require more than one field to be
 * unique (for example <code>(tenantId, appointmentId)</code> in multi-tenant contexts). Not a concept
 * from Evans.</p>
 *
 * <p>All declared properties participate in the identity: <code>getIdentityValue()</code> returns them
 * as an associative array keyed by property name.</p>
 */
interface CompoundIdentity extends Identity, ValueObject
{
}
