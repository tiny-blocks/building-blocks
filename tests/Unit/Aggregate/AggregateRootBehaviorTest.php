<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Aggregate;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use TinyBlocks\BuildingBlocks\Aggregate\AggregateVersion;

final class AggregateRootBehaviorTest extends TestCase
{
    public function testAggregateVersionIsZeroForBlankAggregate(): void
    {
        /** @Given a freshly instantiated aggregate with no events */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @When retrieving the aggregate version */
        $aggregateVersion = $cart->aggregateVersion();

        /** @Then it is zero */
        self::assertSame(0, $aggregateVersion->value);
    }

    public function testModelVersionReflectsDeclaredValue(): void
    {
        /** @Given an aggregate with model version 1 */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));

        /** @When retrieving the model version */
        $version = $cart->modelVersion();

        /** @Then the version reflects the declared value */
        self::assertSame(1, $version->value);
    }

    public function testModelVersionDefaultsToZeroWhenUndefined(): void
    {
        /** @Given an aggregate with the default model version */
        $order = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'pen');

        /** @When retrieving the model version */
        $version = $order->modelVersion();

        /** @Then the default is zero */
        self::assertSame(0, $version->value);
    }

    public function testAggregateTypeForEventSourcedAggregate(): void
    {
        /** @Given a Cart aggregate */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @When retrieving the aggregate type */
        $aggregateType = $cart->aggregateType();

        /** @Then it matches the short class name */
        self::assertSame('Cart', $aggregateType);
    }

    public function testAggregateTypeForOutboxAggregate(): void
    {
        /** @Given an Order aggregate */
        $order = Order::place(orderId: new OrderId(value: 'ord-2'), item: 'lamp');

        /** @When retrieving the aggregate type */
        $aggregateType = $order->aggregateType();

        /** @Then it matches the short class name */
        self::assertSame('Order', $aggregateType);
    }

    public function testReconstitutedAggregateVersionMatchesPersistedValue(): void
    {
        /** @Given an Order reconstituted with a persisted aggregate version of 5 */
        $order = Order::reconstitutePartial(
            identity: new OrderId(value: 'ord-3'),
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @When retrieving the aggregate version */
        $aggregateVersion = $order->aggregateVersion();

        /** @Then it matches the persisted value */
        self::assertSame(5, $aggregateVersion->value);
    }

    public function testPushAfterReconstituteAdvancesVersionByOne(): void
    {
        /** @Given an Order reconstituted with a persisted aggregate version of 5 */
        $order = Order::reconstitutePartial(
            identity: new OrderId(value: 'ord-4'),
            aggregateState: [],
            aggregateVersion: AggregateVersion::of(value: 5)
        );

        /** @When pushing a new event */
        $order->ship(carrier: 'FedEx');

        /** @Then the aggregate version advances by one */
        self::assertSame(6, $order->aggregateVersion()->value);
    }

    public function testPullDrainsRecordedEventsAndClearsTheBuffer(): void
    {
        /** @Given an order with a recorded event */
        $order = Order::place(orderId: new OrderId(value: 'ord-pull'), item: 'pen');

        /** @When pulling the recorded events */
        $pulled = $order->pullEvents();

        /** @Then the pulled batch holds the recorded event */
        self::assertSame(1, $pulled->count());

        /** @And the aggregate buffer is now empty */
        self::assertTrue($order->peekEvents()->isEmpty());
    }
}
