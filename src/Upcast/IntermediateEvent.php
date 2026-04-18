<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\Mapper\ObjectMapper;
use TinyBlocks\Mapper\ObjectMappability;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class IntermediateEvent implements ValueObject, ObjectMapper
{
    use ValueObjectBehavior;
    use ObjectMappability;

    public function __construct(
        public EventType $type,
        public Revision $revision,
        public array $serializedEvent
    ) {
    }

    public function withRevision(Revision $revision): IntermediateEvent
    {
        return new IntermediateEvent(
            type: $this->type,
            revision: $revision,
            serializedEvent: $this->serializedEvent
        );
    }

    public function withSerializedEvent(array $serializedEvent): IntermediateEvent
    {
        return new IntermediateEvent(
            type: $this->type,
            revision: $this->revision,
            serializedEvent: $serializedEvent
        );
    }
}
