<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Entity;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;

/**
 * Cluster of associated objects treated as a single unit for data changes, with one Entity as the root.
 *
 * <p>External references must target the root; invariants apply to the whole cluster; transactions never
 * straddle aggregate boundaries. This interface adds two pragmatic fields absent from Evans:</p>
 *
 * <ul>
 *   <li><code>sequenceNumber</code> for optimistic offline locking.</li>
 *   <li><code>modelVersion</code> for aggregate schema evolution.</li>
 * </ul>
 *
 * <p>Three sibling variants build on this base: plain aggregates without events, {@see EventualAggregateRoot}
 * for the transactional outbox pattern, and {@see EventSourcingRoot} for the event-sourced style.</p>
 *
 * @see Eric Evans, <em>Domain-Driven Design: Tackling Complexity in the Heart of Software</em>
 *      (Addison-Wesley, 2003), Chapter 6 "Aggregates".
 * @see Martin Fowler, <em>Patterns of Enterprise Application Architecture</em> (Addison-Wesley, 2002),
 *      "Optimistic Offline Lock", source of <code>sequenceNumber</code>.
 * @see Greg Young, <em>Versioning in an Event Sourced System</em> (Leanpub, 2017), source of
 *      <code>modelVersion</code>.
 */
interface AggregateRoot extends Entity
{
    /**
     * Returns the aggregate's current sequence number.
     *
     * <p>The initial value is <code>0</code>. The first recorded event increments it to <code>1</code>,
     * and each subsequent event advances it by one. Persistence adapters compare the stored value against
     * the in-memory one to detect concurrent modifications.</p>
     *
     * @return SequenceNumber The current sequence number.
     */
    public function sequenceNumber(): SequenceNumber;

    /**
     * Returns the schema version of this aggregate type.
     *
     * <p>Defaults to <code>ModelVersion::initial()</code> (value 0) when not overridden. Used by consumers
     * to migrate aggregate schemas when loading older persisted state.</p>
     *
     * @return ModelVersion The declared model version.
     */
    public function modelVersion(): ModelVersion;

    /**
     * Returns the short class name of this aggregate.
     *
     * <p>Used as the aggregate type identifier on each produced
     * {@see \TinyBlocks\BuildingBlocks\Event\EventRecord}.</p>
     *
     * @return string The short class name.
     */
    public function aggregateName(): string;
}
