<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidEventType;

final class EventTypeTest extends TestCase
{
    public function testConstructorIsPrivate(): void
    {
        /** @Given the EventType class constructor */
        $constructor = new ReflectionMethod(EventType::class, '__construct');

        /** @When inspecting its visibility */
        /** @Then the constructor is private */
        self::assertTrue($constructor->isPrivate());
    }

    public function testFromEventUsesTheShortClassNameOfTheDomainEvent(): void
    {
        /** @Given a domain event instance */
        $placedEvent = new OrderPlaced(item: 'book');

        /** @When creating an EventType from the event */
        $eventType = EventType::fromEvent(event: $placedEvent);

        /** @Then the value matches the short class name */
        self::assertSame('OrderPlaced', $eventType->value);
    }

    public function testFromStringAcceptsValidPascalCase(): void
    {
        /** @Given a valid PascalCase string */
        /** @When creating an EventType from the string */
        $eventType = EventType::fromString(value: 'OrderShipped');

        /** @Then the value is stored */
        self::assertSame('OrderShipped', $eventType->value);
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        /** @Given two event types with the same name */
        $first = EventType::fromString(value: 'OrderPlaced');

        /** @And a matching counterpart */
        $second = EventType::fromString(value: 'OrderPlaced');

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        /** @Given two event types with different names */
        $first = EventType::fromString(value: 'OrderPlaced');

        /** @And a distinct counterpart */
        $second = EventType::fromString(value: 'OrderShipped');

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    #[DataProvider('invalidPatterns')]
    public function testFromStringRejectsValuesNotMatchingPattern(string $invalidValue): void
    {
        /** @Given a value that violates the event-type pattern */
        /** @Then an InvalidEventType exception mentioning the pattern is thrown */
        $this->expectException(InvalidEventType::class);
        $this->expectExceptionMessage('does not match the required pattern');

        /** @When constructing with the invalid value */
        EventType::fromString(value: $invalidValue);
    }

    public function testInvalidEventTypeIsCatchableAsInvalidArgumentException(): void
    {
        /** @Given consumer code catching the PHP-standard InvalidArgumentException */
        /** @Then InvalidEventType is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with an invalid value */
        EventType::fromString(value: 'invalid');
    }

    public function testInvalidEventTypeCarriesTheOffendingValue(): void
    {
        /** @Given a consumer inspecting the exception message */
        /** @Then the offending value is included in the message */
        $this->expectException(InvalidEventType::class);
        $this->expectExceptionMessage('lowercaseStart');

        /** @When constructing with an invalid value */
        EventType::fromString(value: 'lowercaseStart');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidPatterns(): array
    {
        return [
            'lowercase start' => ['orderPlaced'],
            'contains spaces' => ['Order Placed'],
            'empty string' => [''],
            'contains underscore' => ['Order_Placed'],
            'single character' => ['O']
        ];
    }
}
