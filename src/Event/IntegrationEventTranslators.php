<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\Collection\Collection;

/**
 * Ordered collection of {@see IntegrationEventTranslator} instances.
 *
 * <p>Lookup follows first-match-wins semantics: {@see IntegrationEventTranslators::findFor}
 * returns the first translator whose {@see IntegrationEventTranslator::supports} returns
 * <code>true</code> for the given record, or <code>null</code> when no translator handles
 * it. A null result is the canonical signal that the event is purely internal and must
 * not cross the bounded-context boundary.</p>
 *
 * @extends Collection<IntegrationEventTranslator>
 */
final class IntegrationEventTranslators extends Collection
{
    /**
     * Returns the first translator that supports the given record, or null when none matches.
     *
     * @param EventRecord $record The record whose translator is being resolved.
     * @return IntegrationEventTranslator|null The matching translator, or null when no element
     *                                         supports the record.
     */
    public function findFor(EventRecord $record): ?IntegrationEventTranslator
    {
        return $this->findBy(
            predicates: static fn(IntegrationEventTranslator $translator): bool => $translator->supports(
                record: $record
            )
        );
    }
}
