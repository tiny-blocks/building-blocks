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
            type: $aggregate->aggregateName(),
            createdAt: Instant::now(),
            aggregateId: $aggregate->identityValue(),
            aggregateState: $aggregate->snapshotState(),
            sequenceNumber: $aggregate->sequenceNumber()
        );
    }

    public function type(): string
    {
        return $this->type;
    }

    public function createdAt(): Instant
    {
        return $this->createdAt;
    }

    public function aggregateId(): mixed
    {
        return $this->aggregateId;
    }

    public function aggregateState(): array
    {
        return $this->aggregateState;
    }

    public function sequenceNumber(): SequenceNumber
    {
        return $this->sequenceNumber;
    }
}
