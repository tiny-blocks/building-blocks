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

    /**
     * Rewrites the serialized payload of an event being upcast.
     *
     * <p>Implemented by the consumer. Invoked by {@see upcast()} only when the event's type and revision
     * match this upcaster's declared (type, from-revision) pair. The returned array becomes the new
     * serialized payload at the upcaster's to-revision.</p>
     *
     * @param array<string, mixed> $payload The serialized event payload.
     * @return array<string, mixed> The rewritten payload.
     */
    abstract protected function rewrite(array $payload): array;
}
