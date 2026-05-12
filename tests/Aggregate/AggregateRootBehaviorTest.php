<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Aggregate;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;

final class AggregateRootBehaviorTest extends TestCase
{
    public function testSequenceNumberIsZeroForBlankAggregate(): void
    {
        /** @Given a freshly instantiated aggregate with no events */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @When retrieving the sequence number */
        $sequenceNumber = $cart->sequenceNumber();

        /** @Then it is zero */
        self::assertSame(0, $sequenceNumber->value);
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

    public function testAggregateNameForEventSourcedAggregate(): void
    {
        /** @Given a Cart aggregate */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @When retrieving the aggregate name */
        $name = $cart->aggregateName();

        /** @Then it matches the short class name */
        self::assertSame('Cart', $name);
    }

    public function testAggregateNameForOutboxAggregate(): void
    {
        /** @Given an Order aggregate */
        $order = Order::place(orderId: new OrderId(value: 'ord-2'), item: 'lamp');

        /** @When retrieving the aggregate name */
        $name = $order->aggregateName();

        /** @Then it matches the short class name */
        self::assertSame('Order', $name);
    }
}
