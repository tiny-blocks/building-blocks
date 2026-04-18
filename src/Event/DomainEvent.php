<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

/**
 * Marker interface for records of something that happened in the domain.
 *
 * <p>A Domain Event is a fact that domain experts care about. It has no technical contract beyond being
 * that fact: persistence and transport concerns such as type name, revision, or envelope metadata belong
 * to {@see EventRecord}, not here. Keeping the event pure prevents infrastructure concerns from leaking
 * into the domain model.</p>
 *
 * <p>Being a plain PHP object, any implementation is compatible with PSR-14
 * (<code>Psr\EventDispatcher\EventDispatcherInterface</code>) without additional adaptation.</p>
 *
 * @see Vaughn Vernon, <em>Implementing Domain-Driven Design</em> (Addison-Wesley, 2013), Chapter 8
 *      "Domain Events".
 */
interface DomainEvent
{
}
