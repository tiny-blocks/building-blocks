<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

/**
 * Anti-Corruption Layer between the internal domain model and the public integration
 * contract.
 *
 * <p>Each translator declares, through {@see IntegrationEventTranslator::supports},
 * which {@see EventRecord} it can convert, and produces the corresponding
 * {@see IntegrationEvent} through {@see IntegrationEventTranslator::translate}. A
 * domain event without any registered translator is by definition internal and does
 * not cross the bounded-context boundary.</p>
 *
 * <p>Implementations must be pure functions: no side effects, no external state, no
 * I/O. The translation is the architectural seam where the internal model is curated
 * into the public contract, and curation requires determinism.</p>
 *
 * <p>This library intentionally does not provide a shortcut to publish a
 * {@see DomainEvent} directly without translation. Every event that crosses the
 * boundary must be expressed as an {@see IntegrationEvent}. See the README for the
 * full rationale.</p>
 *
 * @see Vaughn Vernon, <em>Implementing Domain-Driven Design</em> (Addison-Wesley, 2013),
 *      Chapter 3 "Context Maps", section "Anticorruption Layer".
 */
interface IntegrationEventTranslator
{
    /**
     * Tells whether this translator handles the event in the given record.
     *
     * @param EventRecord $record The record being evaluated.
     * @return bool True if this translator can produce an integration event for the record.
     */
    public function supports(EventRecord $record): bool;

    /**
     * Produces the integration event corresponding to the domain event in the record.
     *
     * <p>The caller guarantees that {@see IntegrationEventTranslator::supports} returned
     * <code>true</code> for the same record. Implementations may rely on this and cast
     * <code>$record->event</code> to the concrete domain event class without checking.</p>
     *
     * @param EventRecord $record The record being translated.
     * @return IntegrationEvent The integration event ready to cross the boundary.
     */
    public function translate(EventRecord $record): IntegrationEvent;
}
