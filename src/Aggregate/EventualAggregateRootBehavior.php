<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Exceptions\IncompleteAggregateState;
use TinyBlocks\BuildingBlocks\Internal\AggregateReflection;

trait EventualAggregateRootBehavior
{
    use AggregateRootBehavior;

    public static function reconstitutePartial(
        Identity $identity,
        array $aggregateState,
        AggregateVersion $aggregateVersion
    ): static {
        $aggregate = static::createBlank(identity: $identity);
        AggregateReflection::hydrate(target: $aggregate, state: $aggregateState);
        $aggregate->aggregateVersion = $aggregateVersion;

        return $aggregate;
    }

    public static function reconstituteStrict(
        Identity $identity,
        array $aggregateState,
        AggregateVersion $aggregateVersion
    ): static {
        $aggregate = static::reconstitutePartial(
            identity: $identity,
            aggregateState: $aggregateState,
            aggregateVersion: $aggregateVersion
        );

        $missingProperties = AggregateReflection::uninitializedRequiredProperties(target: $aggregate);

        if ($missingProperties !== []) {
            throw new IncompleteAggregateState(className: static::class, propertyNames: $missingProperties);
        }

        return $aggregate;
    }

    /**
     * Records a domain event on the aggregate's internal buffer.
     *
     * <p>Invoked by command methods of the aggregate after state has been mutated. Advances the
     * aggregate version and appends a fully-built {@see EventRecord} to the recorded-events collection.
     * Not part of the public surface: external callers must go through command methods that establish
     * domain invariants.</p>
     *
     * @param DomainEvent $event The event to record.
     */
    protected function pushEvent(DomainEvent $event): void
    {
        $this->nextAggregateVersion();
        $this->appendRecordedEvent(record: $this->buildEventRecord(event: $event));
    }
}
