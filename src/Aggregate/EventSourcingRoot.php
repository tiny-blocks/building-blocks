<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityProperty;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

/**
 * Aggregate root variant whose state is derived entirely from its ordered stream of events.
 *
 * <p>The event store is the source of truth; aggregate state is a projection. Instances are created
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
     * Returns the explicit map of event class names to handler callables.
     *
     * <p>When the returned array is empty, the trait falls back to the implicit
     * convention <code>when&lt;EventShortName&gt;</code>. When the array is
     * non-empty, it is the authoritative source: only events whose class names
     * appear as keys can be applied; absence triggers an exception.</p>
     *
     * @return array<class-string<DomainEvent>, callable>
     */
    public function eventHandlers(): array;

    /**
     * Returns the events recorded during the current unit of work.
     *
     * @return EventRecords The events awaiting append to the event store.
     */
    public function recordedEvents(): EventRecords;

    /**
     * Creates a blank aggregate with the given identity and no recorded events.
     *
     * <p>The constructor is not invoked. All state must come from events or from a snapshot.</p>
     *
     * @param Identity $identity The identity to assign to the new aggregate.
     * @return static A new aggregate in its initial state.
     * @throws MissingIdentityProperty When the property referenced by <code>identityName()</code> does not exist.
     */
    public static function blank(Identity $identity): static;

    /**
     * Reconstitutes an aggregate by replaying an ordered stream of event records.
     *
     * <p>When a snapshot is provided, the aggregate state is first restored from it and the snapshot's
     * sequence number is taken as authoritative. Only events recorded after the snapshot need to be
     * replayed.</p>
     *
     * @param Identity $identity The identity of the aggregate.
     * @param iterable<EventRecord> $records The event stream to replay, ordered by sequence number.
     * @param Snapshot|null $snapshot Optional snapshot to restore from before replay.
     * @return static The reconstituted aggregate.
     * @throws MissingIdentityProperty When the property referenced by <code>identityName()</code> does not exist.
     */
    public static function reconstitute(Identity $identity, iterable $records, ?Snapshot $snapshot = null): static;

    /**
     * Returns the aggregate state to persist in a snapshot.
     *
     * <p>The default implementation provided by {@see EventSourcingRootBehavior} returns all object
     * properties except <code>recordedEvents</code> (transient buffer) and <code>sequenceNumber</code>
     * (already a first-class field on the snapshot). Override to exclude infrastructure properties
     * (loggers, caches, etc.) or to include only a curated subset of state.</p>
     *
     * @return array<string, mixed> Keyed by property name.
     */
    public function getSnapshotState(): array;

    /**
     * Restores aggregate state from the given snapshot.
     *
     * <p>Implementations read {@see Snapshot::getAggregateState()} and copy the relevant fields into
     * their own properties. The sequence number is applied automatically by
     * <code>reconstitute()</code>; implementations should not touch it.</p>
     *
     * @param Snapshot $snapshot The snapshot to restore from.
     */
    public function applySnapshot(Snapshot $snapshot): void;
}
