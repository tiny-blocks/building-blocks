<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Event\EventRecords;

/**
 * Aggregate root variant that records domain events for eventual publication via transactional outbox.
 *
 * <p>State is persisted as the source of truth; events are emitted as side effects and delivered
 * at-least-once to external consumers. The repository is expected to drain
 * <code>recordedEvents()</code> after persisting the aggregate state and then call
 * <code>clearRecordedEvents()</code> to reset the buffer for the next unit of work.</p>
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
     * Returns a copy of the events recorded since the last clear.
     *
     * <p>Always returns a fresh copy: external mutation of the returned collection does not leak into the
     * aggregate's internal buffer.</p>
     *
     * @return EventRecords A snapshot of the recorded events, safe to iterate and mutate.
     */
    public function recordedEvents(): EventRecords;

    /**
     * Discards all recorded events.
     *
     * <p>Typically called by the repository after the events have been persisted to the outbox.</p>
     */
    public function clearRecordedEvents(): void;
}
