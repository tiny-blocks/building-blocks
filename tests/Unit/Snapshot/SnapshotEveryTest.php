<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Snapshot;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\Cart;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use TinyBlocks\BuildingBlocks\Exceptions\InvalidSnapshotCount;
use TinyBlocks\BuildingBlocks\Snapshot\SnapshotEvery;

final class SnapshotEveryTest extends TestCase
{
    public function testThrowsWhenCountIsZero(): void
    {
        /** @Then an InvalidSnapshotCount exception with the correct message should be thrown */
        $this->expectException(InvalidSnapshotCount::class);
        $this->expectExceptionMessage('Snapshot count must be at least 1, got <0>.');

        /** @When creating a SnapshotEvery with count zero */
        SnapshotEvery::events(count: 0);
    }

    public function testReturnsFalseAtSequenceOne(): void
    {
        /** @Given a cart at sequence 1 */
        $cart = Cart::withProducts(count: 1, cartId: new CartId(value: 'cart-snap-5'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($shouldSnapshot);
    }

    public function testThrowsWhenCountIsNegative(): void
    {
        /** @Then an InvalidSnapshotCount exception with the correct message should be thrown */
        $this->expectException(InvalidSnapshotCount::class);
        $this->expectExceptionMessage('Snapshot count must be at least 1, got <-5>.');

        /** @When creating a SnapshotEvery with a negative count */
        SnapshotEvery::events(count: -5);
    }

    public function testReturnsTrueAtSequenceHundred(): void
    {
        /** @Given a cart at sequence 100 */
        $cart = Cart::withProducts(count: 100, cartId: new CartId(value: 'cart-snap-2'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($shouldSnapshot);
    }

    public function testReturnsTrueAtSequenceTwoHundred(): void
    {
        /** @Given a cart at sequence 200 */
        $cart = Cart::withProducts(count: 200, cartId: new CartId(value: 'cart-snap-3'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($shouldSnapshot);
    }

    public function testReturnsFalseAtSequenceHundredOne(): void
    {
        /** @Given a cart at sequence 101 */
        $cart = Cart::withProducts(count: 101, cartId: new CartId(value: 'cart-snap-7'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($shouldSnapshot);
    }

    public function testReturnsFalseAtSequenceNinetyNine(): void
    {
        /** @Given a cart at sequence 99 */
        $cart = Cart::withProducts(count: 99, cartId: new CartId(value: 'cart-snap-6'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($shouldSnapshot);
    }

    public function testReturnsTrueAtSequenceThreeHundred(): void
    {
        /** @Given a cart at sequence 300 */
        $cart = Cart::withProducts(count: 300, cartId: new CartId(value: 'cart-snap-4'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($shouldSnapshot);
    }

    public function testReturnsFalseAtSequenceOneNinetyNine(): void
    {
        /** @Given a cart at sequence 199 */
        $cart = Cart::withProducts(count: 199, cartId: new CartId(value: 'cart-snap-8'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false */
        self::assertFalse($shouldSnapshot);
    }

    public function testReturnsTrueForCountOneAtSequenceOne(): void
    {
        /** @Given a cart at sequence 1 */
        $cart = Cart::withProducts(count: 1, cartId: new CartId(value: 'cart-snap-9'));

        /** @When asking a count-1 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 1)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($shouldSnapshot);
    }

    public function testReturnsTrueForCountOneAtSequenceTwo(): void
    {
        /** @Given a cart at sequence 2 */
        $cart = Cart::withProducts(count: 2, cartId: new CartId(value: 'cart-snap-10'));

        /** @When asking a count-1 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 1)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is true */
        self::assertTrue($shouldSnapshot);
    }

    public function testReturnsFalseForBlankAggregateWithCountHundred(): void
    {
        /** @Given a blank cart at sequence zero */
        $cart = Cart::blank(identity: new CartId(value: 'cart-snap-1'));

        /** @When asking a count-100 condition whether to snapshot */
        $shouldSnapshot = SnapshotEvery::events(count: 100)->shouldSnapshot(aggregate: $cart);

        /** @Then the result is false because sequence zero is excluded */
        self::assertFalse($shouldSnapshot);
    }
}
