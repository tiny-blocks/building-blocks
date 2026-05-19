<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use ReflectionClass;
use ReflectionProperty;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;

trait EventualAggregateRootBehavior
{
    use AggregateRootBehavior;

    public static function reconstitute(
        Identity $identity,
        AggregateVersion $aggregateVersion,
        array $state = []
    ): static {
        $aggregate = new ReflectionClass(objectOrClass: static::class)->newInstanceWithoutConstructor();
        new ReflectionProperty(class: $aggregate, property: $aggregate->identityName())
            ->setValue($aggregate, $identity);

        foreach ($state as $property => $value) {
            if (property_exists($aggregate, $property)) {
                new ReflectionProperty(class: $aggregate, property: $property)
                    ->setValue($aggregate, $value);
            }
        }

        $aggregate->aggregateVersion = $aggregateVersion;

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
    protected function push(DomainEvent $event): void
    {
        $this->nextAggregateVersion();
        $this->recordedEvents = ($this->recordedEvents ?? EventRecords::createFromEmpty())
            ->add(elements: $this->buildEventRecord(event: $event));
    }
}
