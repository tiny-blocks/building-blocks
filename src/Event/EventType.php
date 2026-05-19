<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use ReflectionClass;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidEventType;
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
     * Creates an EventType from a domain event using its short class name.
     *
     * @param DomainEvent $event The event whose class name carries the type.
     * @return EventType The created instance.
     * @throws InvalidEventType If the resolved class name does not match the required pattern.
     */
    public static function fromEvent(DomainEvent $event): EventType
    {
        return new EventType(value: new ReflectionClass(objectOrClass: $event)->getShortName());
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
}
