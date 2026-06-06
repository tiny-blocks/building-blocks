<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal;

use ReflectionClass;

final class ClassName
{
    private function __construct()
    {
    }

    public static function shortName(object|string $target): string
    {
        return new ReflectionClass(objectOrClass: $target)->getShortName();
    }
}
