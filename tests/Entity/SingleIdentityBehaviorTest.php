<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Entity;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\OrderId;

final class SingleIdentityBehaviorTest extends TestCase
{
    public function testGetIdentityValueReturnsTheSingleScalarField(): void
    {
        /** @Given a single-field identity */
        $orderId = new OrderId(value: 'ord-1');

        /** @When retrieving the identity value */
        $value = $orderId->getIdentityValue();

        /** @Then the scalar value is returned as-is */
        self::assertSame('ord-1', $value);
    }

    public function testEqualsReturnsTrueForIdenticalSingleIdentities(): void
    {
        /** @Given two single identities with the same value */
        $first = new OrderId(value: 'ord-1');

        /** @And a matching counterpart */
        $second = new OrderId(value: 'ord-1');

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are considered equal */
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentSingleIdentities(): void
    {
        /** @Given two single identities with different values */
        $first = new OrderId(value: 'ord-1');

        /** @And a distinct counterpart */
        $second = new OrderId(value: 'ord-2');

        /** @When comparing them */
        $result = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($result);
    }
}
