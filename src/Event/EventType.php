<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use ReflectionClass;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidEventType;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class EventType implements ValueObject
{
    use ValueObjectBehavior;

    private const string PATTERN = '/^[A-Z][A-Za-z0-9]+$/';

    public function __construct(public string $value)
    {
        if (!preg_match(pattern: self::PATTERN, subject: $value)) {
            throw new InvalidEventType(value: $value, pattern: self::PATTERN);
        }
    }

    public static function fromEvent(DomainEvent $event): EventType
    {
        return new EventType(value: new ReflectionClass(objectOrClass: $event)->getShortName());
    }

    public static function fromString(string $value): EventType
    {
        return new EventType(value: $value);
    }
}
