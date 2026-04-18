<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Upcast;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\ProductV1Upcaster;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;

final class SingleUpcasterBehaviorTest extends TestCase
{
    public function testUpcastBumpsTheRevisionOfAMatchingEvent(): void
    {
        /** @Given a ProductAdded event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When upcasting with ProductV1Upcaster */
        $upcasted = new ProductV1Upcaster()->upcast(event: $event);

        /** @Then the revision is bumped to the target value */
        self::assertSame(2, $upcasted->revision->value);
    }

    public function testUpcastEnrichesThePayloadOfAMatchingEvent(): void
    {
        /** @Given a ProductAdded event at revision 1 */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 1),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When upcasting with ProductV1Upcaster */
        $upcasted = new ProductV1Upcaster()->upcast(event: $event);

        /** @Then the payload is enriched with the default quantity */
        self::assertSame(['productId' => 'prod-1', 'quantity' => 1], $upcasted->serializedEvent);
    }

    public function testUpcastReturnsUnchangedEventForMismatchedType(): void
    {
        /** @Given an event whose type is not the one the upcaster handles */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'OrderPlaced'),
            revision: new Revision(value: 1),
            serializedEvent: ['item' => 'book']
        );

        /** @When applying the upcaster */
        $result = new ProductV1Upcaster()->upcast(event: $event);

        /** @Then the same instance is returned unchanged */
        self::assertSame($event, $result);
    }

    public function testUpcastReturnsUnchangedEventForMismatchedRevision(): void
    {
        /** @Given a ProductAdded event at revision 2, past the upcaster's FROM_REVISION */
        $event = new IntermediateEvent(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: new Revision(value: 2),
            serializedEvent: ['productId' => 'prod-1', 'quantity' => 1]
        );

        /** @When applying the upcaster */
        $result = new ProductV1Upcaster()->upcast(event: $event);

        /** @Then the same instance is returned unchanged */
        self::assertSame($event, $result);
    }
}
