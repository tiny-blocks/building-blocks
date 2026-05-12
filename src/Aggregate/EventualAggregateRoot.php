<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Event\EventRecords;

/**
 * Aggregate root variant that records domain events for eventual publication via transactional outbox.
 *
 * <p>State is persisted as the source of truth; events are emitted as side effects and delivered
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
     * Returns a copy of all events recorded since the aggregate was created.
     *
     * <p>Always returns a fresh copy: external mutation of the returned collection does not leak into the
     * aggregate's internal buffer.</p>
     *
     * @return EventRecords A snapshot of the recorded events, safe to iterate and mutate.
     */
    public function recordedEvents(): EventRecords;
}
