<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Event;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\PaymentConfirmed;
use Test\TinyBlocks\BuildingBlocks\Models\PaymentConfirmedV2;

final class IntegrationEventBehaviorTest extends TestCase
{
    public function testDefaultRevisionIsInitial(): void
    {
        /** @Given an integration event using the default IntegrationEventBehavior */
        $event = new PaymentConfirmed(orderId: 'ord-1');

        /** @When retrieving its revision */
        $revision = $event->revision();

        /** @Then the revision is the initial value */
        self::assertSame(1, $revision->value);
    }

    public function testOverriddenRevisionIsReturned(): void
    {
        /** @Given an integration event that overrides revision() */
        $event = new PaymentConfirmedV2(orderId: 'ord-2');

        /** @When retrieving its revision */
        $revision = $event->revision();

        /** @Then the revision matches the override */
        self::assertSame(2, $revision->value);
    }
}
