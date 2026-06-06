<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\BuildingBlocks\Exceptions\InvalidEventType;
use TinyBlocks\BuildingBlocks\Internal\ClassName;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class EventType implements ValueObject
{
    use ValueObjectBehavior;

    private const string PATTERN = '/^[A-Z][A-Za-z0-9]+$/';

    private function __construct(public string $value)
    {
        if (!preg_match(self::PATTERN, $value)) {
            throw new InvalidEventType(value: $value, pattern: self::PATTERN);
        }
    }

    /**
     * Creates an EventType from a raw type identifier.
     *
     * @param string $value The PascalCase type identifier.
     * @return EventType The created instance.
     * @throws InvalidEventType If the value does not match the required pattern.
     */
    public static function fromString(string $value): EventType
    {
        return new EventType(value: $value);
    }

    /**
     * Creates an EventType from a domain event using its declared type identifier.
     *
     * @param DomainEvent $event The domain event whose <code>eventType()</code> carries the type.
     * @return EventType The created instance.
     * @throws InvalidEventType If the declared type does not match the required pattern.
     */
    public static function fromDomainEvent(DomainEvent $event): EventType
    {
        return new EventType(value: $event->eventType());
    }

    /**
     * Creates an EventType from an integration event using its short class name.
     *
     * @param IntegrationEvent $event The integration event whose class name carries the type.
     * @return EventType The created instance.
     * @throws InvalidEventType If the resolved class name does not match the required pattern.
     */
    public static function fromIntegrationEvent(IntegrationEvent $event): EventType
    {
        return new EventType(value: ClassName::shortName(target: $event));
    }
}
