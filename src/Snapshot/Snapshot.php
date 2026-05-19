<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Snapshot;

use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;
use TinyBlocks\BuildingBlocks\Aggregate\EventSourcingRoot;
use TinyBlocks\Time\Instant;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class Snapshot implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(
        public string $aggregateType,
        public Instant $createdAt,
        public mixed $aggregateId,
        public array $aggregateState,
        public AggregateVersion $aggregateVersion
    ) {
    }

    /**
     * Creates a Snapshot from the persisted fields.
     *
     * @param string $aggregateType The short class name of the aggregate.
     * @param Instant $createdAt The instant the snapshot was taken.
     * @param mixed $aggregateId The aggregate identity raw value.
     * @param array<string, mixed> $aggregateState The captured aggregate state keyed by property name.
     * @param AggregateVersion $aggregateVersion The aggregate version captured with the snapshot.
     * @return Snapshot The restored snapshot instance.
     */
    public static function restore(
        string $aggregateType,
        Instant $createdAt,
        mixed $aggregateId,
        array $aggregateState,
        AggregateVersion $aggregateVersion
    ): Snapshot {
        return new Snapshot(
            aggregateType: $aggregateType,
            createdAt: $createdAt,
            aggregateId: $aggregateId,
            aggregateState: $aggregateState,
            aggregateVersion: $aggregateVersion
        );
    }

    /**
     * Creates a Snapshot from the current state of the given aggregate.
     *
     * @param EventSourcingRoot $aggregate The aggregate to snapshot.
     * @return Snapshot The captured snapshot.
     */
    public static function fromAggregate(EventSourcingRoot $aggregate): Snapshot
    {
        return new Snapshot(
            aggregateType: $aggregate->aggregateType(),
            createdAt: Instant::now(),
            aggregateId: $aggregate->identityValue(),
            aggregateState: $aggregate->snapshotState(),
            aggregateVersion: $aggregate->aggregateVersion()
        );
    }

    /**
     * Returns the aggregate type.
     *
     * @return string The short class name of the snapshotted aggregate.
     */
    public function aggregateType(): string
    {
        return $this->aggregateType;
    }

    /**
     * Returns the creation timestamp.
     *
     * @return Instant The instant the snapshot was taken.
     */
    public function createdAt(): Instant
    {
        return $this->createdAt;
    }

    /**
     * Returns the aggregate identity raw value.
     *
     * @return mixed The identity value captured with the snapshot.
     */
    public function aggregateId(): mixed
    {
        return $this->aggregateId;
    }

    /**
     * Returns the aggregate state as an associative array.
     *
     * @return array<string, mixed> The captured state keyed by property name.
     */
    public function aggregateState(): array
    {
        return $this->aggregateState;
    }

    /**
     * Returns the aggregate version.
     *
     * @return AggregateVersion The aggregate version captured with the snapshot.
     */
    public function aggregateVersion(): AggregateVersion
    {
        return $this->aggregateVersion;
    }
}
