<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

use TinyBlocks\Collection\Collection;

final class Upcasters extends Collection
{
    public function chain(IntermediateEvent $event): IntermediateEvent
    {
        $upcast = static function (IntermediateEvent $carried, Upcaster $upcaster): IntermediateEvent {
            return $upcaster->upcast(event: $carried);
        };

        return $this->reduce(accumulator: $upcast, initial: $event);
    }
}
