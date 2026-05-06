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
    public function testGetSequenceNumberIsZeroForBlankAggregate(): void
    {
        /** @Given a freshly instantiated aggregate with no events */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @When retrieving the sequence number */
        $sequenceNumber = $cart->getSequenceNumber();

        /** @Then it is zero */
        self::assertSame(0, $sequenceNumber->value);
    }

    public function testGetModelVersionReflectsDeclaredConstant(): void
    {
        /** @Given an aggregate with model version 1 */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));

        /** @When retrieving the model version */
        $version = $cart->getModelVersion();

        /** @Then the version reflects the declared value */
        self::assertSame(1, $version->value);
    }

    public function testGetModelVersionDefaultsToZeroWhenUndefined(): void
    {
        /** @Given an aggregate with the default model version */
        $order = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'pen');

        /** @When retrieving the model version */
        $version = $order->getModelVersion();

        /** @Then the default is zero */
        self::assertSame(0, $version->value);
    }

    public function testBuildAggregateNameForEventSourcedAggregate(): void
    {
        /** @Given a Cart aggregate */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @When building the aggregate name */
        $name = $cart->buildAggregateName();

        /** @Then it matches the short class name */
        self::assertSame('Cart', $name);
    }

    public function testBuildAggregateNameForOutboxAggregate(): void
    {
        /** @Given an Order aggregate */
        $order = Order::place(orderId: new OrderId(value: 'ord-2'), item: 'lamp');

        /** @When building the aggregate name */
        $name = $order->buildAggregateName();

        /** @Then it matches the short class name */
        self::assertSame('Order', $name);
    }
}
