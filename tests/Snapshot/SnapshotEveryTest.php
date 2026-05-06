<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidSnapshotCount;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotEvery;

final class SnapshotEveryTest extends TestCase
{
    public function testReturnsFalseForBlankAggregateWithCountHundred(): void
    {
        /** @Given a blank cart at sequence zero */
        $cart = Cart::blank(identity: new CartId(value: 'cart-snap-1'));

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false because sequence zero is excluded */
        self::assertFalse($result);
    }

    public function testReturnsTrueAtSequenceHundred(): void
    {
        /** @Given a cart at sequence 100 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-2'), count: 100);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($result);
    }

    public function testReturnsTrueAtSequenceTwoHundred(): void
    {
        /** @Given a cart at sequence 200 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-3'), count: 200);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($result);
    }

    public function testReturnsTrueAtSequenceThreeHundred(): void
    {
        /** @Given a cart at sequence 300 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-4'), count: 300);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($result);
    }

    public function testReturnsFalseAtSequenceOne(): void
    {
        /** @Given a cart at sequence 1 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-5'), count: 1);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($result);
    }

    public function testReturnsFalseAtSequenceNinetyNine(): void
    {
        /** @Given a cart at sequence 99 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-6'), count: 99);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($result);
    }

    public function testReturnsFalseAtSequenceHundredOne(): void
    {
        /** @Given a cart at sequence 101 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-7'), count: 101);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($result);
    }

    public function testReturnsFalseAtSequenceOneNinetyNine(): void
    {
        /** @Given a cart at sequence 199 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-8'), count: 199);

        /** @When asking a count-100 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($result);
    }

    public function testReturnsTrueForCountOneAtSequenceOne(): void
    {
        /** @Given a cart at sequence 1 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-9'), count: 1);

        /** @When asking a count-1 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 1)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($result);
    }

    public function testReturnsTrueForCountOneAtSequenceTwo(): void
    {
        /** @Given a cart at sequence 2 */
        $cart = Cart::withProducts(cartId: new CartId(value: 'cart-snap-10'), count: 2);

        /** @When asking a count-1 condition whether to snapshot */
        $result = SnapshotEvery::events(count: 1)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($result);
    }

    public function testThrowsWhenCountIsZero(): void
    {
        /** @Then an InvalidSnapshotCount exception with the correct message should be thrown */
        $this->expectException(InvalidSnapshotCount::class);
        $this->expectExceptionMessage('Snapshot count must be at least 1, got <0>.');

        /** @When creating a SnapshotEvery with count zero */
        SnapshotEvery::events(count: 0);
    }

    public function testThrowsWhenCountIsNegative(): void
    {
        /** @Then an InvalidSnapshotCount exception with the correct message should be thrown */
        $this->expectException(InvalidSnapshotCount::class);
        $this->expectExceptionMessage('Snapshot count must be at least 1, got <-5>.');

        /** @When creating a SnapshotEvery with a negative count */
        SnapshotEvery::events(count: -5);
    }
}
