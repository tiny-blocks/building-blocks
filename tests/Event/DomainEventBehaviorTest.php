<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Event;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\OrderPlaced;
use Test\TinyBlocks\BuildingBlocks\Models\ProductAddedV2;

final class DomainEventBehaviorTest extends TestCase
{
    public function testDefaultRevisionIsInitial(): void
    {
        /** @Given an event using the default DomainEventBehavior */
        $event = new OrderPlaced(item: 'book');

        /** @When retrieving its revision */
        $revision = $event->revision();

        /** @Then the revision is the initial value */
        self::assertSame(1, $revision->value);
    }

    public function testOverriddenRevisionIsReturned(): void
    {
        /** @Given an event that overrides revision() */
        $event = new ProductAddedV2(productId: 'prod-1', quantity: 2);

        /** @When retrieving its revision */
        $revision = $event->revision();

        /** @Then the revision matches the override */
        self::assertSame(2, $revision->value);
    }
}
