<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

use TinyBlocks\Collection\Collection;

/**
 * @extends Collection<Upcaster>
 */
final class Upcasters extends Collection
{
    /**
     * Folds every upcaster in this collection over the given event, returning the resulting event.
     *
     * <p>Each upcaster either advances the event by one (type, revision) step or returns it unchanged.
     * Apply order follows the collection's iteration order, so callers must register upcasters in the
     * order they should run.</p>
     *
     * @param IntermediateEvent $event The event entering the chain.
     * @return IntermediateEvent The event after every upcaster in the chain has been applied.
     */
    public function chain(IntermediateEvent $event): IntermediateEvent
    {
        $upcast = static function (IntermediateEvent $carried, Upcaster $upcaster): IntermediateEvent {
            return $upcaster->upcast(event: $carried);
        };

        $upcasted = $this->reduce(accumulator: $upcast, initial: $event);

        return $upcasted;
    }
}
