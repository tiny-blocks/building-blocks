<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use ReflectionClass;
use ReflectionProperty;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\EventHandlerMethodNotFound;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\NoEventHandlerRegistered;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

trait EventSourcingRootBehavior
{
    use AggregateRootBehavior;

    public static function blank(Identity $identity): static
    {
        $aggregate = new ReflectionClass(static::class)->newInstanceWithoutConstructor();
        new ReflectionProperty($aggregate, $aggregate->identityName())
            ->setValue($aggregate, $identity);
        $aggregate->sequenceNumber = SequenceNumber::initial();
        $aggregate->recordedEvents = EventRecords::createFromEmpty();

        return $aggregate;
    }

    public static function reconstitute(
        Identity $identity,
        iterable $records,
        ?Snapshot $snapshot = null
    ): static {
        $aggregate = static::blank(identity: $identity);

        if (!is_null($snapshot)) {
            $aggregate->applySnapshot(snapshot: $snapshot);
            $aggregate->sequenceNumber = $snapshot->getSequenceNumber();
        }

        foreach ($records as $record) {
            $aggregate->applyEvent(record: $record);
        }

        return $aggregate;
    }

    public function eventHandlers(): array
    {
        return [];
    }

    public function getSnapshotState(): array
    {
        $state = get_object_vars($this);
        unset($state['recordedEvents'], $state['sequenceNumber']);

        return $state;
    }

    protected function when(DomainEvent $event): void
    {
        $this->nextSequenceNumber();
        $record = $this->buildEventRecord(event: $event);
        $this->applyEvent(record: $record);
        $this->recordedEvents = ($this->recordedEvents ?? EventRecords::createFromEmpty())
            ->add(elements: $record);
    }

    protected function applyEvent(EventRecord $record): void
    {
        $handlers = $this->eventHandlers();
        $eventClass = $record->event::class;

        if ($handlers !== []) {
            if (!array_key_exists($eventClass, $handlers)) {
                throw new NoEventHandlerRegistered(eventClass: $eventClass, aggregateClass: static::class);
            }

            $handlers[$eventClass]($record->event);
            $this->sequenceNumber = $record->sequenceNumber;
            return;
        }

        $methodName = sprintf('when%s', $record->type->value);

        if (!method_exists($this, $methodName)) {
            throw new EventHandlerMethodNotFound(methodName: $methodName, aggregateClass: static::class);
        }

        $this->{$methodName}($record->event);
        $this->sequenceNumber = $record->sequenceNumber;
    }
}
