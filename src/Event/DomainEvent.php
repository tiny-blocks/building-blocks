<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

/**
 * Marker interface for records of something that happened in the domain.
 *
 * <p>A Domain Event carries only the data describing <strong>what happened</strong> in the domain. It is
 * a fact that domain experts care about. The following concerns explicitly do <strong>not</strong> belong
 * on a <code>DomainEvent</code> and must not be exposed as accessors on subtypes:</p>
 *
 * <ul>
 *   <li><strong>Aggregate identity</strong> — added by the aggregate when building the {@see EventRecord}.</li>
 *   <li><strong>Aggregate type</strong> — derived from the aggregate's class name into the envelope.</li>
 *   <li><strong>Sequence number</strong> — assigned by the aggregate into the envelope.</li>
 *   <li><strong>Serialization to storage</strong> — responsibility of outbox writers and consumer
 *       deserializers, both infrastructure concerns outside this library.</li>
 * </ul>
 *
 * <p>Adding accessors for any of the above to a <code>DomainEvent</code> subtype duplicates information
 * already present on the {@see EventRecord} envelope and pulls infrastructure into the domain layer.</p>
 *
 * <p>Each event declares its own schema {@see Revision} via the <code>revision()</code> method, defaulted
 * to {@see Revision::initial} by {@see DomainEventBehavior}. Override only when bumping schema. Being a
 * plain PHP object otherwise, any implementation remains compatible with PSR-14
 * (<code>Psr\EventDispatcher\EventDispatcherInterface</code>) without additional adaptation.</p>
 *
 * @see Vaughn Vernon, <em>Implementing Domain-Driven Design</em> (Addison-Wesley, 2013), Chapter 8
 *      "Domain Events".
 */
interface DomainEvent
{
    /**
     * Returns the schema revision of this event.
     *
     * @return Revision The current schema revision; defaults to {@see Revision::initial}.
     */
    public function revision(): Revision;
}
