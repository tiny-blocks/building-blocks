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
        $result = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition holds because zero is divisible by two */
        self::assertTrue($result);
    }

    public function testConditionDoesNotHoldAfterOneEvent(): void
    {
        /** @Given a cart advanced to sequence number one */
        $cart = Cart::blank(identity: new CartId(value: 'cart-2'));
        $cart->addProduct(productId: 'prod-1');

        /** @When asking the condition whether to snapshot */
        $result = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold */
        self::assertFalse($result);
    }

    public function testConditionHoldsAgainAfterTwoEvents(): void
    {
        /** @Given a cart advanced to sequence number two */
        $cart = Cart::blank(identity: new CartId(value: 'cart-3'));
        $cart->addProduct(productId: 'prod-1');
        $cart->addProduct(productId: 'prod-2');

        /** @When asking the condition whether to snapshot */
        $result = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition holds again at the next even step */
        self::assertTrue($result);
    }

    public function testConditionDoesNotHoldAfterThreeEvents(): void
    {
        /** @Given a cart advanced to sequence number three */
        $cart = Cart::blank(identity: new CartId(value: 'cart-4'));
        $cart->addProduct(productId: 'prod-1');
        $cart->addProduct(productId: 'prod-2');
        $cart->addProduct(productId: 'prod-3');

        /** @When asking the condition whether to snapshot */
        $result = new EveryTwoEvents()->shouldSnapshot(aggregate: $cart);

        /** @Then the condition does not hold at an odd step */
        self::assertFalse($result);
    }
}
