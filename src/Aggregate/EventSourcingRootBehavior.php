<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use LogicException;
use ReflectionClass;
use ReflectionProperty;
use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Event\EventRecords;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

trait EventSourcingRootBehavior
{
    use AggregateRootBehavior;

    private EventRecords $recordedEvents;

    public function recordedEvents(): EventRecords
    {
        $records = $this->recordedEvents ?? EventRecords::createFromEmpty();

        return EventRecords::createFrom(elements: $records);
    }

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

    protected function when(DomainEvent $event, Revision $revision): void
    {
        $this->nextSequenceNumber();
        $record = $this->buildEventRecord(event: $event, revision: $revision);
        $this->applyEvent(record: $record);
        $this->recordedEvents = ($this->recordedEvents ?? EventRecords::createFromEmpty())->add($record);
    }

    protected function applyEvent(EventRecord $record): void
    {
        $eventClass = $record->event::class;
        $separatorPosition = strrpos($eventClass, '\\');
        $shortName = $separatorPosition === false ? $eventClass : substr($eventClass, $separatorPosition + 1);
        $methodName = sprintf('when%s', $shortName);

        if (!method_exists($this, $methodName)) {
            $template = 'Handler method <%s> not found in aggregate <%s>.';

            throw new LogicException(sprintf($template, $methodName, static::class));
        }

        $this->{$methodName}($record->event);
        $this->sequenceNumber = $record->sequenceNumber;
    }
}
