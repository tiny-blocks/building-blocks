<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\Time\Instant;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Snapshot implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(
        private string $type,
        private Instant $createdAt,
        private mixed $aggregateId,
        private array $aggregateState,
        private SequenceNumber $sequenceNumber
    ) {
    }

    public static function restore(
        string $type,
        Instant $createdAt,
        mixed $aggregateId,
        array $aggregateState,
        SequenceNumber $sequenceNumber
    ): Snapshot {
        return new Snapshot(
            type: $type,
            createdAt: $createdAt,
            aggregateId: $aggregateId,
            aggregateState: $aggregateState,
            sequenceNumber: $sequenceNumber
        );
    }

    public static function fromAggregate(EventSourcingRoot $aggregate): Snapshot
    {
        return new Snapshot(
            type: $aggregate->buildAggregateName(),
            createdAt: Instant::now(),
            aggregateId: $aggregate->getIdentityValue(),
            aggregateState: $aggregate->getSnapshotState(),
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
