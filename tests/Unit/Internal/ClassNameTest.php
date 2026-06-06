<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Internal;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use TinyBlocks\BuildingBlocks\Internal\ClassName;

final class ClassNameTest extends TestCase
{
    public function testConstructorWhenInvokedThroughReflectionThenRemainsPrivate(): void
    {
        /** @Given the ClassName private constructor reflected */
        $constructor = new ReflectionMethod(objectOrMethod: ClassName::class, method: '__construct');

        /** @When invoking it through reflection on an instance allocated without it */
        $constructor->invoke($constructor->getDeclaringClass()->newInstanceWithoutConstructor());

        /** @Then the constructor is private */
        self::assertTrue($constructor->isPrivate());
    }
}
