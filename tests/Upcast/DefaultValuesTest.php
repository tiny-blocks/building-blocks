<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Upcast;

use PHPUnit\Framework\TestCase;
use TinyBlocks\BuildingBlocks\Upcast\DefaultValues;

final class DefaultValuesTest extends TestCase
{
    public function testDefaultsMapYieldsTheZeroValueForEachPrimitiveType(): void
    {
        /** @Given the primitive defaults map */
        /** @When retrieving it */
        $defaults = DefaultValues::get();

        /** @Then each primitive type maps to its zero-value */
        self::assertSame(0, $defaults['int']);
        self::assertFalse($defaults['bool']);
        self::assertSame([], $defaults['array']);
        self::assertSame(0.0, $defaults['float']);
        self::assertSame('', $defaults['string']);
    }

    public function testDefaultsMapContainsOnlyPrimitiveTypeKeys(): void
    {
        /** @Given the primitive defaults map */
        /** @When retrieving it */
        $defaults = DefaultValues::get();

        /** @Then only primitive type keys are present */
        self::assertSame(['int', 'bool', 'array', 'float', 'string'], array_keys($defaults));
    }
}
