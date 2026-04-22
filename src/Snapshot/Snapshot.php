<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use ReflectionObject;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\Time\Instant;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Snapshot implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(
        private string $type,
        private Instant $createdAt,
        private mixed $aggregateId,
        private array $aggregateState,
        private SequenceNumber $sequenceNumber
    ) {
    }

    public static function fromAggregate(EventSourcingRoot $aggregate): Snapshot
    {
        $reflection = new ReflectionObject($aggregate);
        $aggregateState = [];

        foreach ($reflection->getProperties() as $property) {
            if (!in_array($property->getName(), ['recordedEvents', 'sequenceNumber'], true)) {
                $aggregateState[$property->getName()] = $property->getValue($aggregate);
            }
        }

        return new Snapshot(
            type: $aggregate->buildAggregateName(),
            createdAt: Instant::now(),
            aggregateId: $aggregate->getIdentityValue(),
            aggregateState: $aggregateState,
            sequenceNumber: $aggregate->getSequenceNumber()
        );
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): Instant
    {
        return $this->createdAt;
    }

    public function getAggregateId(): mixed
    {
        return $this->aggregateId;
    }

    public function getAggregateState(): array
    {
        return $this->aggregateState;
    }

    public function getSequenceNumber(): SequenceNumber
    {
        return $this->sequenceNumber;
    }
}
