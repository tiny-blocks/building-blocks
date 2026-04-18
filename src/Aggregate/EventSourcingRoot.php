<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityConstant;
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
     * @throws MissingIdentityConstant When the <code>IDENTITY</code> class constant is not defined.
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
     * @throws MissingIdentityConstant When the <code>IDENTITY</code> class constant is not defined.
     */
    public static function reconstitute(Identity $identity, iterable $records, ?Snapshot $snapshot = null): static;

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
