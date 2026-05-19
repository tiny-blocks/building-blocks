<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Upcast;

final class DefaultValues
{
    /**
     * Returns the DefaultValues as an associative array keyed by primitive type.
     *
     * @return array<string, mixed> Zero-value for each primitive type.
     */
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
