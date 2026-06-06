<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Internal;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TinyBlocks\BuildingBlocks\Internal\AggregateReflection;

final class AggregateReflectionTest extends TestCase
{
    public function testConstructorWhenInvokedThroughReflectionThenRemainsPrivate(): void
    {
        /** @Given the AggregateReflection private constructor reflected */
        $constructor = new ReflectionMethod(objectOrMethod: AggregateReflection::class, method: '__construct');

        /** @When invoking it through reflection on an instance allocated without it */
        $constructor->invoke($constructor->getDeclaringClass()->newInstanceWithoutConstructor());

        /** @Then the constructor is private */
        self::assertTrue($constructor->isPrivate());
    }
}
