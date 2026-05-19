<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Exceptions\MissingIdentityProperty;

/**
 * Aggregate root variant that records domain events for eventual publication via transactional outbox.
 *
 * <p>State is persisted as the source of truth. Events are emitted as side effects and delivered
 * at-least-once to external consumers. The repository drains <code>recordedEvents()</code> after
 * persisting the aggregate state.</p>
 *
 * <p><strong>Use-once contract:</strong> the recorded-events buffer is never cleared. After the
 * repository drains <code>recordedEvents()</code> and persists the records to the outbox, the aggregate
 * instance must be discarded. Re-saving the same instance attempts to push the same envelopes again and
 * fails with a duplicate-event error from the outbox. Applications that need to perform multiple
 * operations on the same logical aggregate within one process must reload from the repository between
 * operations.</p>
 *
 * <p>Sibling of {@see EventSourcingRoot}, not a parent. Outbox and event sourcing are mutually exclusive
 * persistence strategies: an aggregate either persists its state and emits events as side effects, or
 * persists only its events as the source of truth.</p>
 *
 * @see Vaughn Vernon, <em>Implementing Domain-Driven Design</em> (Addison-Wesley, 2013), Chapter 8
 *      "Domain Events".
 * @see Chris Richardson, <em>Microservices Patterns</em> (Manning, 2018), Chapter 3, "Transactional Outbox".
 */
interface EventualAggregateRoot extends AggregateRoot
{
    /**
     * Reconstitutes the aggregate from persisted state.
     *
     * <p>Default factory for state-based aggregates. Used by repositories that load aggregate state
     * from external storage (a relational row, a document, an in-memory cache) and need an instance
     * ready to emit further events from the correct aggregate version.</p>
     *
     * <p>The default implementation provided by {@see EventualAggregateRootBehavior} hydrates state
     * properties by reflection from the <code>$state</code> map. Aggregates may override this factory
     * to customize the hydration or to rename the identity parameter to reflect their specific
     * identity type. For example:</p>
     *
     * <pre>
     * // Override with a custom parameter name:
     * public static function reconstitute(
     *     Identity $orderId,
     *     AggregateVersion $aggregateVersion,
     *     array $state = []
     * ): static
     * </pre>
     *
     * <p>The parameter name is free, but the type must remain <code>Identity</code> per LSP rules on
     * static methods. Concrete identity types (e.g. <code>OrderId</code>) can be enforced inside the
     * override via <code>instanceof</code>.</p>
     *
     * @param Identity $identity The aggregate's identity.
     * @param AggregateVersion $aggregateVersion The version to restore. Subsequent emitted events
     *                                           advance from this value.
     * @param array<string, mixed> $state Optional map of property name to value. Entries whose key
     *                                    does not match a declared property are silently ignored by
     *                                    the default implementation.
     * @return static The reconstituted aggregate.
     * @throws MissingIdentityProperty When the property referenced by <code>identityProperty()</code>
     *                                 does not exist on the aggregate class.
     */
    public static function reconstitute(
        Identity $identity,
        AggregateVersion $aggregateVersion,
        array $state = []
    ): static;

    /**
     * Returns a copy of all events recorded since the aggregate was created.
     *
     * <p>Always returns a fresh copy: external mutation of the returned collection does not leak into
     * the aggregate's internal buffer.</p>
     *
     * @return EventRecords A snapshot of the recorded events, safe to iterate and mutate.
     */
    public function recordedEvents(): EventRecords;
}
