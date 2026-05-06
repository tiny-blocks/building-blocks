<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotNever;

final class SnapshotNeverTest extends TestCase
{
    public function testReturnsFalseForBlankAggregate(): void
    {
        /** @Given a blank cart */
        $cart = Cart::blank(identity: new CartId(value: 'cart-never-1'));

        /** @When asking the SnapshotNever condition whether to snapshot */
        $result = SnapshotNever::create()->shouldSnapshot(aggregate: $cart);

        /** @Then the result is always false */
        self::assertFalse($result);
    }

    public function testReturnsFalseForAggregateAtHighSequenceNumber(): void
    {
        /** @Given a cart at sequence 1000 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-never-2'), count: 1000);

        /** @When asking the SnapshotNever condition whether to snapshot */
        $result = SnapshotNever::create()->shouldSnapshot(aggregate: $cart);

        /** @Then the result is always false */
        self::assertFalse($result);
    }

    public function testTwoInstancesAreEqualUnderLooseComparison(): void
    {
        /** @Given two separate SnapshotNever instances */
        $first = SnapshotNever::create();

        /** @And a second instance */
        $second = SnapshotNever::create();

        /** @Then both instances are equal under loose comparison */
        self::assertEquals($first, $second);
    }
}
