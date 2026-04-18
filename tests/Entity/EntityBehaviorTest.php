<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Entity;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\AppointmentId;
use Test\TinyBlocks\BuildingBlocks\Models\Order;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;
use Test\TinyBlocks\BuildingBlocks\Models\OrderWithMissingIdentityProperty;
use Test\TinyBlocks\BuildingBlocks\Models\OrderWithoutIdentityConstant;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityConstant;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityProperty;

final class EntityBehaviorTest extends TestCase
{
    public function testGetIdentityReturnsHeldIdentity(): void
    {
        /** @Given an order constructed with a known identity */
        $orderId = new OrderId(value: 'ord-1');

        /** @And the aggregate placed for that identity */
        $order = Order::place(orderId: $orderId, item: 'book');

        /** @When retrieving the identity */
        $identity = $order->getIdentity();

        /** @Then the same identity instance is returned */
        self::assertSame($orderId, $identity);
    }

    public function testGetIdentityNameReturnsPropertyName(): void
    {
        /** @Given an order aggregate */
        $order = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'pen');

        /** @When retrieving the identity property name */
        $name = $order->getIdentityName();

        /** @Then it matches the IDENTITY constant value */
        self::assertSame('orderId', $name);
    }

    public function testGetIdentityValueReturnsScalarForSingleIdentity(): void
    {
        /** @Given an order whose identity is a single-value identifier */
        $order = Order::place(orderId: new OrderId(value: 'ord-42'), item: 'pen');

        /** @When retrieving the identity value */
        $value = $order->getIdentityValue();

        /** @Then the raw scalar is returned */
        self::assertSame('ord-42', $value);
    }

    public function testGetIdentityValueReturnsAssociativeArrayForCompoundIdentity(): void
    {
        /** @Given a compound identity */
        $appointmentId = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @When retrieving the identity value */
        $value = $appointmentId->getIdentityValue();

        /** @Then an associative array with all fields is returned */
        self::assertSame(['tenantId' => 'tenant-1', 'appointmentId' => 'apt-1'], $value);
    }

    public function testSameIdentityOfReturnsTrueForAggregatesWithEqualIdentity(): void
    {
        /** @Given two orders sharing the same identity value */
        $first = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'book');

        /** @And a second order with the same identity value */
        $second = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'pen');

        /** @When comparing their identities */
        $result = $first->sameIdentityOf(other: $second);

        /** @Then the comparison yields true */
        self::assertTrue($result);
    }

    public function testSameIdentityOfReturnsFalseForAggregatesWithDifferentIdentity(): void
    {
        /** @Given two orders with different identities */
        $first = Order::place(orderId: new OrderId(value: 'ord-1'), item: 'book');

        /** @And a second order with a different identity */
        $second = Order::place(orderId: new OrderId(value: 'ord-2'), item: 'pen');

        /** @When comparing their identities */
        $result = $first->sameIdentityOf(other: $second);

        /** @Then the comparison yields false */
        self::assertFalse($result);
    }

    public function testIdentityEqualsReturnsTrueForEqualIdentity(): void
    {
        /** @Given an order and an identity with the same value */
        $order = Order::place(orderId: new OrderId(value: 'ord-5'), item: 'lamp');

        /** @And a separately constructed identity of equal value */
        $sameIdentity = new OrderId(value: 'ord-5');

        /** @When comparing the identity */
        $result = $order->identityEquals(other: $sameIdentity);

        /** @Then the comparison yields true */
        self::assertTrue($result);
    }

    public function testIdentityEqualsReturnsFalseForDifferentIdentity(): void
    {
        /** @Given an order and an identity with a different value */
        $order = Order::place(orderId: new OrderId(value: 'ord-5'), item: 'lamp');

        /** @And a different identity value */
        $otherIdentity = new OrderId(value: 'ord-9');

        /** @When comparing the identity */
        $result = $order->identityEquals(other: $otherIdentity);

        /** @Then the comparison yields false */
        self::assertFalse($result);
    }

    public function testShipThrowsWhenIdentityConstantIsMissing(): void
    {
        /** @Given an aggregate whose class omits the IDENTITY constant */
        $order = new OrderWithoutIdentityConstant();

        /** @Then a MissingIdentityConstant exception carrying the class name is thrown */
        $this->expectException(MissingIdentityConstant::class);
        $this->expectExceptionMessage(OrderWithoutIdentityConstant::class);

        /** @When shipping the order and indirectly reaching identity resolution */
        $order->ship();
    }

    public function testShipThrowsWhenIdentityPropertyIsMissing(): void
    {
        /** @Given an aggregate whose IDENTITY points to a non-existent property */
        $order = new OrderWithMissingIdentityProperty();

        /** @Then a MissingIdentityProperty exception carrying the property name is thrown */
        $this->expectException(MissingIdentityProperty::class);
        $this->expectExceptionMessage('nonExistentProperty');

        /** @When shipping the order and indirectly reaching identity resolution */
        $order->ship();
    }
}
