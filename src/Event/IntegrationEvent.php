<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

/**
 * Marker interface for facts published outside the producing bounded context.
 *
 * <p>An <code>IntegrationEvent</code> is the stable public contract that flows across
 * bounded contexts, typically through a transactional outbox and an asynchronous relay.
 * It is a sibling of {@see DomainEvent}, not a subtype: domain events describe what
 * happened inside the model and evolve freely with it; integration events describe
 * what external consumers can rely on and must remain backward-compatible.</p>
 *
 * <p>Translation from a {@see DomainEvent} to an <code>IntegrationEvent</code> happens
 * at the boundary via an {@see IntegrationEventTranslator}, which acts as the
 * Anti-Corruption Layer between the internal model and the public contract.</p>
 *
 * <p>Each integration event declares its own schema {@see Revision} via the
 * <code>revision()</code> method, defaulted to {@see Revision::initial} by
 * {@see IntegrationEventBehavior}. Override only when bumping the public schema.</p>
 *
 * @see Vaughn Vernon, <em>Implementing Domain-Driven Design</em> (Addison-Wesley, 2013),
 *      Chapter 3 "Context Maps" and Chapter 13 "Integrating Bounded Contexts".
 */
interface IntegrationEvent
{
    /**
     * Returns the schema revision of this integration event.
     *
     * @return Revision The current schema revision. Defaults to {@see Revision::initial}.
     */
    public function revision(): Revision;
}
