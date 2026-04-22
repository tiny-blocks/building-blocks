<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Upcast;

use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;
use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;

final class IntermediateEventTest extends TestCase
{
    public function testIntermediateEventExposesEveryConstructorField(): void
    {
        /** @Given every required field for an IntermediateEvent */
        $eventType = EventType::fromString(value: 'ProductAdded');
        $revision = Revision::initial();
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
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When bumping to revision 2 */
        $bumped = $event->withRevision(revision: Revision::of(value: 2));

        /** @Then the revision changes */
        self::assertSame(2, $bumped->revision->value);
    }

    public function testWithRevisionPreservesTheTypeAndPayload(): void
    {
        /** @Given an intermediate event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When bumping to revision 2 */
        $bumped = $event->withRevision(revision: Revision::of(value: 2));

        /** @Then neither the type nor the payload are affected */
        self::assertSame('ProductAdded', $bumped->type->value);
        self::assertSame(['productId' => 'prod-1'], $bumped->serializedEvent);
    }

    public function testWithRevisionReturnsANewInstance(): void
    {
        /** @Given an intermediate event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When bumping to revision 2 */
        $bumped = $event->withRevision(revision: Revision::of(value: 2));

        /** @Then the source event remains untouched */
        self::assertNotSame($event, $bumped);
        self::assertSame(1, $event->revision->value);
    }

    public function testWithSerializedEventOnlyReplacesThePayload(): void
    {
        /** @Given an intermediate event with an original payload */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
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
            revision: Revision::initial(),
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
        $revision = Revision::initial();
        $payload = ['productId' => 'prod-1'];

        /** @And two intermediate events with identical values */
        $first = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: $payload);
        $second = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: $payload);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are equal */
        self::assertTrue($areEqual);
    }

    public function testEqualsReturnsFalseForDifferentPayloads(): void
    {
        /** @Given two intermediate events with different payloads */
        $eventType = EventType::fromString(value: 'ProductAdded');
        $revision = Revision::initial();

        /** @And the two events constructed accordingly */
        $first = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: ['productId' => 'a']);
        $second = new IntermediateEvent(type: $eventType, revision: $revision, serializedEvent: ['productId' => 'b']);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenOnlyTypeDiffers(): void
    {
        /** @Given two intermediate events sharing revision and payload */
        $revision = Revision::initial();

        /** @And differing only by type */
        $first = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: $revision,
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @And a counterpart with a different type */
        $second = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductRemoved'),
            revision: $revision,
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenOnlyRevisionDiffers(): void
    {
        /** @Given two intermediate events sharing type and payload */
        $eventType = EventType::fromString(value: 'ProductAdded');

        /** @And differing only by revision */
        $first = new IntermediateEvent(
            type: $eventType,
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @And a counterpart at a later revision */
        $second = new IntermediateEvent(
            type: $eventType,
            revision: Revision::of(value: 2),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenOtherIsDifferentValueObjectType(): void
    {
        /** @Given an intermediate event */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @And a value object of a different class */
        $otherValueObject = SequenceNumber::first();

        /** @When comparing them */
        $areEqual = $event->equals(other: $otherValueObject);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testFromIterableWithTypedFieldsCreatesEqualEvent(): void
    {
        /** @Given an existing intermediate event */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When reconstituting from an iterable of typed values */
        $restored = IntermediateEvent::fromIterable(iterable: [
            'type'            => EventType::fromString(value: 'ProductAdded'),
            'revision'        => Revision::initial(),
            'serializedEvent' => ['productId' => 'prod-1']
        ]);

        /** @Then the restored event equals the original */
        self::assertTrue($restored->equals(other: $event));
    }

    public function testToArraySerializesTypeAndRevisionToScalars(): void
    {
        /** @Given an intermediate event */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When converting to array */
        $array = $event->toArray();

        /** @Then type and revision are unwrapped to their scalar values */
        self::assertSame('ProductAdded', $array['type']);
        self::assertSame(1, $array['revision']);
        self::assertSame(['productId' => 'prod-1'], $array['serializedEvent']);
    }

    public function testToJsonSerializesToJsonString(): void
    {
        /** @Given an intermediate event */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When converting to JSON */
        $json = $event->toJson();

        /** @Then the result is a valid JSON string with the expected structure */
        self::assertSame(
            '{"type":"ProductAdded","revision":1,"serializedEvent":{"productId":"prod-1"}}',
            $json
        );
    }
}
