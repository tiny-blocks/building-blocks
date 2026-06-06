<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Exceptions\MissingIdentityProperty;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

/**
 * Aggregate root variant whose state is derived entirely from its ordered stream of events.
 *
 * <p>The event store is the source of truth. Aggregate state is a projection. Instances are created
 * through {@see blank()} and populated by replaying events via {@see reconstitute()}, optionally starting
 * from a {@see Snapshot} to skip earlier events.</p>
 *
 * <p>Sibling of {@see EventualAggregateRoot}, not a parent. The default implementation instantiates
 * aggregates via reflection without invoking the constructor, so implementations must derive all state
 * from events or from a snapshot, never from constructor logic.</p>
 *
 * @see Greg Young, <em>CQRS Documents</em> (2010), "Event Sourcing".
 * @see Vaughn Vernon, <em>Implementing Domain-Driven Design</em> (Addison-Wesley, 2013), Chapter 8,
 *      "Event Sourcing" section.
 */
interface EventSourcingRoot extends AggregateRoot
{
    /**
     * Creates a blank aggregate with the given identity and no recorded events.
     *
     * <p>The constructor is not invoked. All state must come from events or from a snapshot.</p>
     *
     * @param Identity $identity The identity to assign to the new aggregate.
     * @return static A new aggregate in its initial state.
     * @throws MissingIdentityProperty If the property referenced by <code>identityProperty()</code> does not exist.
     */
    public static function blank(Identity $identity): static;

    /**
     * Reconstitutes an aggregate by replaying an ordered stream of event records.
     *
     * <p>When a snapshot is provided, the aggregate state is first restored from it and the snapshot's
     * aggregate version is taken as authoritative. Only events recorded after the snapshot need to be
     * replayed.</p>
     *
     * @param iterable<EventRecord> $records The event stream to replay, ordered by aggregate version.
     * @param Identity $identity The identity of the aggregate.
     * @param Snapshot|null $snapshot Optional snapshot to restore from before replay.
     * @return static The reconstituted aggregate.
     * @throws MissingIdentityProperty If the property referenced by <code>identityProperty()</code> does not exist.
     */
    public static function reconstitute(iterable $records, Identity $identity, ?Snapshot $snapshot = null): static;

    /**
     * Returns the aggregate state to persist in a snapshot.
     *
     * <p>The default implementation provided by {@see EventSourcingRootBehavior} returns all object
     * properties except <code>recordedEvents</code> (transient buffer) and <code>aggregateVersion</code>
     * (already a first-class field on the snapshot). Override to exclude infrastructure properties
     * (loggers, caches, etc.) or to include only a curated subset of state.</p>
     *
     * @return array<string, mixed> Keyed by property name.
     */
    public function snapshotState(): array;

    /**
     * Restores aggregate state from the given snapshot.
     *
     * <p>Implementations read {@see Snapshot::aggregateState()} and copy the relevant fields into
     * their own properties. The aggregate version is applied automatically by
     * <code>reconstitute()</code>. Implementations should not touch it.</p>
     *
     * @param Snapshot $snapshot The snapshot to restore from.
     */
    public function applySnapshot(Snapshot $snapshot): void;

    /**
     * Returns the explicit map of event class names to handler callables.
     *
     * <p>When the returned array is empty, the trait falls back to the implicit
     * convention <code>whenEventShortName</code>. When the array is
     * non-empty, it is the authoritative source: only events whose class names
     * appear as keys can be applied. Absence triggers an exception.</p>
     *
     * @return array<class-string<DomainEvent>, callable> The explicit event-class to handler map.
     */
    public function eventHandlers(): array;

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
