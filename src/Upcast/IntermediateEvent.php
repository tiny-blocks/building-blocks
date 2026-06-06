<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\Mapper\Mappable;
use TinyBlocks\Mapper\MappableBehavior;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class IntermediateEvent implements ValueObject, Mappable
{
    use ValueObjectBehavior;
    use MappableBehavior;

    private function __construct(
        public EventType $type,
        public Revision $revision,
        public array $serializedEvent
    ) {
    }

    /**
     * Creates an IntermediateEvent from its type, revision, and serialized payload.
     *
     * @param EventType $type The event type identifier.
     * @param Revision $revision The schema revision.
     * @param array<string, mixed> $serializedEvent The serialized event payload.
     * @return IntermediateEvent The created instance.
     */
    public static function from(EventType $type, Revision $revision, array $serializedEvent): IntermediateEvent
    {
        return new IntermediateEvent(type: $type, revision: $revision, serializedEvent: $serializedEvent);
    }

    public function equals(ValueObject $other): bool
    {
        if ($other::class !== static::class) {
            return false;
        }

        /** @var IntermediateEvent $other */
        return $this->type->equals(other: $other->type)
            && $this->revision->equals(other: $other->revision)
            && $this->serializedEvent === $other->serializedEvent;
    }

    /**
     * Returns a copy of the IntermediateEvent with the revision replaced.
     *
     * @param Revision $revision The replacement revision.
     * @return IntermediateEvent A new instance carrying the given revision.
     */
    public function withRevision(Revision $revision): IntermediateEvent
    {
        return new IntermediateEvent(
            type: $this->type,
            revision: $revision,
            serializedEvent: $this->serializedEvent
        );
    }

    /**
     * Returns a copy of the IntermediateEvent with the serialized payload replaced.
     *
     * @param array<string, mixed> $serializedEvent The replacement payload.
     * @return IntermediateEvent A new instance carrying the given payload.
     */
    public function withSerializedEvent(array $serializedEvent): IntermediateEvent
    {
        return new IntermediateEvent(
            type: $this->type,
            revision: $this->revision,
            serializedEvent: $serializedEvent
        );
    }
}
