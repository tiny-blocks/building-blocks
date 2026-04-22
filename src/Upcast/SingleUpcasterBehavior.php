<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

use TinyBlocks\BuildingBlocks\Event\Revision;

trait SingleUpcasterBehavior
{
    public function upcast(IntermediateEvent $event): IntermediateEvent
    {
        if ($event->type->value !== static::EXPECTED_EVENT_TYPE) {
            return $event;
        }

        if ($event->revision->value !== static::FROM_REVISION) {
            return $event;
        }

        return $event
            ->withSerializedEvent(serializedEvent: $this->rewrite(payload: $event->serializedEvent))
            ->withRevision(revision: Revision::of(value: static::TO_REVISION));
    }

    abstract protected function rewrite(array $payload): array;
}
