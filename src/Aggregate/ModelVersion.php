<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Aggregate;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\InvalidModelVersion;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class ModelVersion implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(public int $value)
    {
        if ($value < 0) {
            throw new InvalidModelVersion(value: $value);
        }
    }

    public static function of(int $value): ModelVersion
    {
        return new ModelVersion(value: $value);
    }

    public static function initial(): ModelVersion
    {
        return new ModelVersion(value: 0);
    }
}
