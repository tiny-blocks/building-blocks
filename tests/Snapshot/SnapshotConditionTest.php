<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotEvery;

final class SnapshotConditionTest extends TestCase
{
    public function testConditionDoesNotHoldAtInitialSequence(): void
    {
        /** @Given a blank cart at sequence number zero */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 2)->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold at zero */
        self::assertFalse($shouldSnapshot);
    }

    public function testConditionDoesNotHoldAfterOneEvent(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));

        /** @And one product added advancing the sequence to one */
        $cart->addProduct(productId: 'prod-1');

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 2)->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold */
        self::assertFalse($shouldSnapshot);
    }

    public function testConditionHoldsAfterTwoEvents(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @And a first product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a second product advancing the sequence to two */
        $cart->addProduct(productId: 'prod-2');

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 2)->shouldSnapshot(aggregate: $cart);

        /** @Then the condition holds at the first positive multiple */
        self::assertTrue($shouldSnapshot);
    }

    public function testConditionDoesNotHoldAfterThreeEvents(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));

        /** @And a first product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a second product added */
        $cart->addProduct(productId: 'prod-2');

        /** @And a third product advancing the sequence to three */
        $cart->addProduct(productId: 'prod-3');

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 2)->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold at an odd step */
        self::assertFalse($shouldSnapshot);
    }
}
