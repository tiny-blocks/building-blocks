<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Aggregate;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use TinyBlocks\BuildingBlocks\Event\SequenceNumber;

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

    public function testReconstitutedSequenceNumberMatchesPersistedValue(): void
    {
        /** @Given an Order reconstituted with a persisted sequence number of 5 */
        $order = Order::reconstitute(orderId: new OrderId(value: 'ord-3'), sequenceNumber: SequenceNumber::of(value: 5));

        /** @When retrieving the sequence number */
        $sequenceNumber = $order->sequenceNumber();

        /** @Then it matches the persisted value */
        self::assertSame(5, $sequenceNumber->value);
    }

    public function testPushAfterReconstituteAdvancesSequenceByOne(): void
    {
        /** @Given an Order reconstituted with a persisted sequence number of 5 */
        $order = Order::reconstitute(orderId: new OrderId(value: 'ord-4'), sequenceNumber: SequenceNumber::of(value: 5));

        /** @When pushing a new event */
        $order->ship(carrier: 'FedEx');

        /** @Then the sequence number advances by one */
        self::assertSame(6, $order->sequenceNumber()->value);
    }
}
