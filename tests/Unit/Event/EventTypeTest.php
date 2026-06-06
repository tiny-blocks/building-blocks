<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\PaymentConfirmed;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\IntegrationEvent;
use TinyBlocks\BuildingBlocks\Event\IntegrationEventBehavior;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidEventType;

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

    public function testFromDomainEventUsesTheDeclaredEventTypeIdentifier(): void
    {
        /** @Given a domain event instance */
        $placedEvent = new OrderPlaced(item: 'book');

        /** @When creating an EventType from the domain event */
        $eventType = EventType::fromDomainEvent(event: $placedEvent);

        /** @Then the value matches the event's declared type identifier */
        self::assertSame('OrderPlaced', $eventType->value);
    }

    public function testFromIntegrationEventUsesTheShortClassNameOfTheIntegrationEvent(): void
    {
        /** @Given an integration event instance */
        $paymentConfirmed = new PaymentConfirmed(orderId: 'ord-1');

        /** @When creating an EventType from the integration event */
        $eventType = EventType::fromIntegrationEvent(event: $paymentConfirmed);

        /** @Then the value matches the short class name */
        self::assertSame('PaymentConfirmed', $eventType->value);
    }

    public function testFromIntegrationEventThrowsWhenClassNameDoesNotMatchPattern(): void
    {
        /** @Given an integration event whose short class name does not match the required pattern */
        $event = new class implements IntegrationEvent {
            use IntegrationEventBehavior;
        };

        /** @Then an InvalidEventType exception is thrown */
        $this->expectException(InvalidEventType::class);

        /** @When creating an EventType from the anonymous integration event */
        EventType::fromIntegrationEvent(event: $event);
    }

    public function testFromStringAcceptsValidPascalCase(): void
    {
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
        /** @Then an InvalidEventType exception mentioning the pattern is thrown */
        $this->expectException(InvalidEventType::class);
        $this->expectExceptionMessage('does not match the required pattern');

        /** @When constructing with the invalid value */
        EventType::fromString(value: $invalidValue);
    }

    public function testInvalidEventTypeIsCatchableAsInvalidArgumentException(): void
    {
        /** @Then InvalidEventType is caught by the standard exception type */
        $this->expectException(InvalidArgumentException::class);

        /** @When constructing with an invalid value */
        EventType::fromString(value: 'invalid');
    }

    public function testInvalidEventTypeCarriesTheOffendingValue(): void
    {
        /** @Then the offending value is included in the message */
        $this->expectException(InvalidEventType::class);
        $this->expectExceptionMessage('lowercaseStart');

        /** @When constructing with an invalid value */
        EventType::fromString(value: 'lowercaseStart');
    }

    public static function invalidPatterns(): array
    {
        return [
            'lowercase start'     => ['orderPlaced'],
            'contains spaces'     => ['Order Placed'],
            'empty string'        => [''],
            'contains underscore' => ['Order_Placed'],
            'single character'    => ['O']
        ];
    }
}
