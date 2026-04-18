<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Upcast;

use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;

final class IntermediateEventTest extends TestCase
{
    public function testIntermediateEventExposesEveryConstructorField(): void
    {
        /** @Given every required field for an IntermediateEvent */
        $eventType = EventType::fromString(value: 'ProductAdded');
        $revision = new Revision(value: 1);
        $serializedEvent = ['productId' => 'prod-1'];

        /** @When constructing the intermediate event */
        $event = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: $serializedEvent);

        /** @Then each public field is accessible */
        self::assertSame($eventType, $event->type);
        self::assertSame($revision, $event->revision);
        self::assertSame($serializedEvent, $event->serializedEvent);
    }

    public function testWithRevisionOnlyReplacesTheRevision(): void
    {
        /** @Given an intermediate event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When bumping to revision 2 */
        $bumped = $event->withRevision(revision: new Revision(value: 2));

        /** @Then the revision changes */
        self::assertSame(2, $bumped->revision->value);
    }

    public function testWithRevisionPreservesTheTypeAndPayload(): void
    {
        /** @Given an intermediate event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When bumping to revision 2 */
        $bumped = $event->withRevision(revision: new Revision(value: 2));

        /** @Then neither the type nor the payload are affected */
        self::assertSame('ProductAdded', $bumped->type->value);
        self::assertSame(['productId' => 'prod-1'], $bumped->serializedEvent);
    }

    public function testWithRevisionReturnsANewInstance(): void
    {
        /** @Given an intermediate event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When bumping to revision 2 */
        $bumped = $event->withRevision(revision: new Revision(value: 2));

        /** @Then the source event remains untouched */
        self::assertNotSame($event, $bumped);
        self::assertSame(1, $event->revision->value);
    }

    public function testWithSerializedEventOnlyReplacesThePayload(): void
    {
        /** @Given an intermediate event with an original payload */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When replacing the serialized payload */
        $updated = $event->withSerializedEvent(serializedEvent: ['productId' => 'prod-1', 'quantity' => 1]);

        /** @Then the payload changes */
        self::assertSame(['productId' => 'prod-1', 'quantity' => 1], $updated->serializedEvent);
    }

    public function testWithSerializedEventPreservesTheTypeAndRevision(): void
    {
        /** @Given an intermediate event with an original payload */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When replacing the serialized payload */
        $updated = $event->withSerializedEvent(serializedEvent: ['productId' => 'prod-1', 'quantity' => 1]);

        /** @Then neither the type nor the revision are affected */
        self::assertSame(1, $updated->revision->value);
        self::assertSame('ProductAdded', $updated->type->value);
    }

    public function testEqualsReturnsTrueForIdenticalIntermediateEvents(): void
    {
        /** @Given shared fields for two intermediate events */
        $eventType = EventType::fromString(value: 'ProductAdded');
        $revision = new Revision(value: 1);
        $payload = ['productId' => 'prod-1'];

        /** @And two intermediate events with identical values */
        $first = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: $payload);
        $second = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: $payload);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentPayloads(): void
    {
        /** @Given two intermediate events with different payloads */
        $eventType = EventType::fromString(value: 'ProductAdded');
        $revision = new Revision(value: 1);

        /** @And the two events constructed accordingly */
        $first = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: ['productId' => 'a']);
        $second = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: ['productId' => 'b']);

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($result);
    }
}
