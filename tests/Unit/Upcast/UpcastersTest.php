<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Upcast;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\ProductV1Upcaster;
use Test\TinyBlocks\BuildingBlocks\Models\ProductV2Upcaster;
use TinyBlocks\BuildingBlocks\Event\EventType;
use TinyBlocks\BuildingBlocks\Event\Revision;
use TinyBlocks\BuildingBlocks\Upcast\IntermediateEvent;
use TinyBlocks\BuildingBlocks\Upcast\Upcasters;

final class UpcastersTest extends TestCase
{
    public function testEmptyChainReturnsEventUnchanged(): void
    {
        /** @Given an event at revision 1 */
        $event = IntermediateEvent::from(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @When chaining through an empty Upcasters collection */
        $chained = Upcasters::createFromEmpty()->chain(event: $event);

        /** @Then the event is returned unchanged */
        self::assertTrue($chained->equals(other: $event));
    }

    public function testSingleMatchingUpcasterTransformsEvent(): void
    {
        /** @Given an event at revision 1 eligible for V1 migration */
        $event = IntermediateEvent::from(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @And a chain containing only the V1 upcaster */
        $upcasters = Upcasters::createFrom(elements: [new ProductV1Upcaster()]);

        /** @When chaining the event */
        $chained = $upcasters->chain(event: $event);

        /** @Then the revision advances to 2 and the payload gains the quantity field */
        self::assertSame(2, $chained->revision->value);
        self::assertSame(['productId' => 'prod-1', 'quantity' => 1], $chained->serializedEvent);
    }

    public function testSingleNonMatchingUpcasterReturnsEventUnchanged(): void
    {
        /** @Given an event at revision 2 — past the V1 migration window */
        $event = IntermediateEvent::from(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::of(value: 2),
            serializedEvent: ['productId' => 'prod-1', 'quantity' => 1]
        );

        /** @And a chain containing only the V1 upcaster */
        $upcasters = Upcasters::createFrom(elements: [new ProductV1Upcaster()]);

        /** @When chaining the event */
        $chained = $upcasters->chain(event: $event);

        /** @Then the event is returned unchanged */
        self::assertTrue($chained->equals(other: $event));
    }

    public function testChainedUpcastersApplySequentially(): void
    {
        /** @Given an event at revision 1 eligible for both V1 and V2 migrations */
        $event = IntermediateEvent::from(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::initial(),
            serializedEvent: ['productId' => 'prod-1']
        );

        /** @And a chain with both upcasters in order */
        $upcasters = Upcasters::createFrom(elements: [new ProductV1Upcaster(), new ProductV2Upcaster()]);

        /** @When chaining the event */
        $chained = $upcasters->chain(event: $event);

        /** @Then the revision reaches 3 and both fields are added */
        self::assertSame(3, $chained->revision->value);
        self::assertSame(['productId' => 'prod-1', 'quantity' => 1, 'notes' => ''], $chained->serializedEvent);
    }

    public function testOnlyMatchingUpcastersInChainApply(): void
    {
        /** @Given an event at revision 2 — only eligible for V2 migration */
        $event = IntermediateEvent::from(
            type: EventType::fromString(value: 'ProductAdded'),
            revision: Revision::of(value: 2),
            serializedEvent: ['productId' => 'prod-1', 'quantity' => 1]
        );

        /** @And a chain with both upcasters */
        $upcasters = Upcasters::createFrom(elements: [new ProductV1Upcaster(), new ProductV2Upcaster()]);

        /** @When chaining the event */
        $chained = $upcasters->chain(event: $event);

        /** @Then only V2 applies: revision advances to 3 and notes is added */
        self::assertSame(3, $chained->revision->value);
        self::assertSame(['productId' => 'prod-1', 'quantity' => 1, 'notes' => ''], $chained->serializedEvent);
    }
}
