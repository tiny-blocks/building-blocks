<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Exceptions\IncompleteAggregateState;
use TinyBlocks\BuildingBlocks\Exceptions\MissingIdentityProperty;

/**
 * Aggregate root variant that records domain events for eventual publication via transactional outbox.
 *
 * <p>State is persisted as the source of truth. Events are emitted as side effects and delivered
 * at-least-once to external consumers. After persisting the aggregate state, the repository drains the
 * recorded-events buffer with <code>pullEvents()</code>, which returns the events and clears the buffer, so a
 * second save of the same instance does not re-emit the events already drained. <code>peekEvents()</code> is the
 * non-destructive counterpart: it returns a fresh copy for inspection and leaves the buffer untouched.</p>
 *
 * <p>An instance models a single unit of work. Once its events have been drained and persisted, reload from
 * the repository before operating on the same logical aggregate again rather than reusing the drained
 * instance.</p>
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
     * Reconstitutes the aggregate from persisted state without verifying completeness.
     *
     * <p>Default factory for state-based aggregates. Used by repositories that load aggregate state from
     * external storage (a relational row, a document, an in-memory cache) and need an instance ready to
     * emit further events from the correct aggregate version. The default implementation provided by
     * {@see EventualAggregateRootBehavior} hydrates state properties by reflection from the
     * <code>$aggregateState</code> map.</p>
     *
     * <p>Aggregates may override this factory to customize the hydration or to rename the identity
     * parameter to reflect their specific identity type. The type must remain <code>Identity</code> per
     * LSP rules on static methods. Concrete identity types can be enforced inside the override via
     * <code>instanceof</code>.</p>
     *
     * @param Identity $identity The aggregate's identity.
     * @param array<string, mixed> $aggregateState Map of property name to value. Unknown keys are ignored.
     * @param AggregateVersion $aggregateVersion The version to restore. Emitted events advance from this value.
     * @return static The reconstituted aggregate.
     * @throws MissingIdentityProperty When the property referenced by <code>identityProperty()</code> does not exist.
     */
    public static function reconstitutePartial(
        Identity $identity,
        array $aggregateState,
        AggregateVersion $aggregateVersion
    ): static;

    /**
     * Reconstitutes the aggregate and verifies every property was initialized.
     *
     * <p>Strict variant of {@see reconstitutePartial()}. It delegates to <code>reconstitutePartial</code>,
     * honoring any override, then checks by reflection that hydration left no declared property
     * uninitialized. Properties that carry a default value, and untyped properties, are always initialized
     * by PHP and so are never flagged.</p>
     *
     * @param Identity $identity The aggregate's identity.
     * @param array<string, mixed> $aggregateState Map of property name to value. Unknown keys are ignored.
     * @param AggregateVersion $aggregateVersion The version to restore. Emitted events advance from this value.
     * @return static The reconstituted aggregate, with every property guaranteed initialized.
     * @throws IncompleteAggregateState If hydration left any property uninitialized.
     * @throws MissingIdentityProperty When the property referenced by <code>identityProperty()</code> does not exist.
     */
    public static function reconstituteStrict(
        Identity $identity,
        array $aggregateState,
        AggregateVersion $aggregateVersion
    ): static;

    /**
     * Returns a fresh copy of the recorded events without clearing the buffer.
     *
     * <p>A non-destructive read. The returned collection is a copy, so external mutation does not leak
     * into the aggregate's internal buffer, and the buffer is left intact for a later {@see pullEvents()} to
     * drain.</p>
     *
     * @return EventRecords A copy of the recorded events, safe to iterate and mutate.
     */
    public function peekEvents(): EventRecords;

    /**
     * Returns the recorded events and clears the internal buffer.
     *
     * <p>Drains the buffer in a single step: the returned collection holds every event recorded since the
     * last drain, and a subsequent call returns an empty collection until new events are recorded.</p>
     *
     * @return EventRecords The events recorded since the last drain.
     */
    public function pullEvents(): EventRecords;
}
