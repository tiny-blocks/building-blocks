<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

final readonly class DefaultValues
{
    public static function get(): array
    {
        return [
            'int'    => 0,
            'bool'   => false,
            'array'  => [],
            'float'  => 0.0,
            'string' => ''
        ];
    }
}
