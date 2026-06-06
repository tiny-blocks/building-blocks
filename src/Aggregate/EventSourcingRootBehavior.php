<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Entity\Identity;
use TinyBlocks\BuildingBlocks\Event\DomainEvent;
use TinyBlocks\BuildingBlocks\Event\EventRecord;
use TinyBlocks\BuildingBlocks\Exceptions\EventHandlerMethodNotFound;
use TinyBlocks\BuildingBlocks\Exceptions\NoEventHandlerRegistered;
use TinyBlocks\BuildingBlocks\Snapshot\Snapshot;

trait EventSourcingRootBehavior
{
    use AggregateRootBehavior;

    public static function blank(Identity $identity): static
    {
        $aggregate = static::createBlank(identity: $identity);
        $aggregate->aggregateVersion = AggregateVersion::initial();

        return $aggregate;
    }

    public static function reconstitute(
        iterable $records,
        Identity $identity,
        ?Snapshot $snapshot = null
    ): static {
        $aggregate = static::blank(identity: $identity);

        if (!is_null($snapshot)) {
            $aggregate->applySnapshot(snapshot: $snapshot);
            $aggregate->aggregateVersion = $snapshot->aggregateVersion();
        }

        foreach ($records as $record) {
            $aggregate->applyEvent(record: $record);
        }

        return $aggregate;
    }

    public function snapshotState(): array
    {
        /** @var array<string, mixed> $state */
        $state = get_object_vars($this);
        unset($state['recordedEvents'], $state['aggregateVersion']);

        return $state;
    }

    public function eventHandlers(): array
    {
        return [];
    }

    /**
     * Records a domain event and applies it to the aggregate's state in one step.
     *
     * <p>Invoked by command methods of an event-sourced aggregate. Advances the aggregate version,
     * builds the {@see EventRecord}, applies it via the registered handler or the implicit
     * <code>whenEventShortName</code> convention, and appends the record to the recorded-events
     * collection. Not part of the public surface.</p>
     *
     * @param DomainEvent $event The event to record and apply.
     */
    protected function when(DomainEvent $event): void
    {
        $this->nextAggregateVersion();
        $record = $this->buildEventRecord(event: $event);
        $this->applyEvent(record: $record);
        $this->appendRecordedEvent(record: $record);
    }

    private function applyEvent(EventRecord $record): void
    {
        $handlers = $this->eventHandlers();
        $eventClass = $record->event::class;

        if ($handlers !== []) {
            if (!array_key_exists($eventClass, $handlers)) {
                throw new NoEventHandlerRegistered(eventClass: $eventClass, aggregateClass: static::class);
            }

            $handlers[$eventClass]($record->event);
            $this->aggregateVersion = $record->aggregateVersion;
            return;
        }

        $methodName = sprintf('when%s', $record->eventType->value);

        if (!method_exists($this, $methodName)) {
            throw new EventHandlerMethodNotFound(methodName: $methodName, aggregateClass: static::class);
        }

        $this->{$methodName}($record->event);
        $this->aggregateVersion = $record->aggregateVersion;
    }
}
