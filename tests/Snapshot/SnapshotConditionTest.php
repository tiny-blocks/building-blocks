<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\EveryTwoEvents;

final class SnapshotConditionTest extends TestCase
{
    public function testConditionHoldsAtInitialSequence(): void
    {
        /** @Given a blank cart at sequence number zero */
        $cart = Cart::blank(identity: new CartId(value: 'cart-1'));

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition holds because zero is divisible by two */
        self::assertTrue($shouldSnapshot);
    }

    public function testConditionDoesNotHoldAfterOneEvent(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));

        /** @And one product added advancing the sequence to one */
        $cart->addProduct(productId: 'prod-1');

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold */
        self::assertFalse($shouldSnapshot);
    }

    public function testConditionHoldsAgainAfterTwoEvents(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));

        /** @And a first product added */
        $cart->addProduct(productId: 'prod-1');

        /** @And a second product advancing the sequence to two */
        $cart->addProduct(productId: 'prod-2');

        /** @When asking the condition whether to snapshot */
        $shouldSnapshot = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition holds again at the next even step */
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
        $shouldSnapshot = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold at an odd step */
        self::assertFalse($shouldSnapshot);
    }
}
