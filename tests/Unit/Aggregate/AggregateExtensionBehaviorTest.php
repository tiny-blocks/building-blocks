<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Aggregate;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\CartId;
use Test\TinyBlocks\BuildingBlocks\Models\ReservationId;
use Test\TinyBlocks\BuildingBlocks\Models\SpecializedCart;
use Test\TinyBlocks\BuildingBlocks\Models\SpecializedReservation;

final class AggregateExtensionBehaviorTest extends TestCase
{
    public function testConfirmWhenSubclassPushesEventThenVersionAdvances(): void
    {
        /** @Given a booked reservation specialized by a subclass */
        $reservation = SpecializedReservation::book(id: new ReservationId(value: 'res-1'));

        /** @When the subclass command records an event through the inherited push seam */
        $reservation->confirm();

        /** @Then the aggregate version reflects both the booking and the confirmation */
        self::assertSame(2, $reservation->aggregateVersion()->value);
    }

    public function testAddGiftProductWhenSubclassRecordsEventThenStateIsApplied(): void
    {
        /** @Given a blank cart specialized by a subclass */
        $cart = SpecializedCart::blank(identity: new CartId(value: 'cart-1'));

        /** @When the subclass command records an event through the inherited recording seam */
        $cart->addGiftProduct(productId: 'gift-1');

        /** @Then the recorded event is applied to the aggregate state */
        self::assertSame(['gift-1'], $cart->productIds());
    }

    public function testStartEmptyWhenSubclassCallsInheritedFactoryThenVersionIsInitial(): void
    {
        /** @Given a cart identity */
        $cartId = new CartId(value: 'cart-2');

        /** @When the subclass custom factory builds a blank instance through the inherited factory seam */
        $cart = SpecializedCart::startEmpty(cartId: $cartId);

        /** @Then the aggregate starts at the initial version carrying the given identity */
        self::assertSame(0, $cart->aggregateVersion()->value);
    }

    public function testIdentityPropertyNameWhenSubclassReadsInheritedSeamThenDefaultResolves(): void
    {
        /** @Given a blank cart specialized by a subclass that reads its inherited identity convention */
        $cart = SpecializedCart::blank(identity: new CartId(value: 'cart-3'));

        /** @When the subclass resolves its backing property name through the inherited seam */
        $name = $cart->identityPropertyName();

        /** @Then it resolves to the default property declared by the inherited behavior */
        self::assertSame('id', $name);
    }
}
