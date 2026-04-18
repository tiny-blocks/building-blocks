<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

final readonly class SnapshotData implements ValueObject
{
    use ValueObjectBehavior;

    public function __construct(private array $data)
    {
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function toJson(int $flags = JSON_PRESERVE_ZERO_FRACTION): string
    {
        return json_encode(value: $this->data, flags: $flags | JSON_THROW_ON_ERROR);
    }
}
