<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Upcast\SingleUpcasterBehavior;
use TinyBlocks\BuildingBlocks\Upcast\Upcaster;

final class ProductV1Upcaster implements Upcaster
{
    use SingleUpcasterBehavior;

    private const string EXPECTED_EVENT_TYPE = 'ProductAdded';
    private const int FROM_REVISION = 1;
    private const int TO_REVISION = 2;

    protected function rewrite(array $payload): array
    {
        return [...$payload, 'quantity' => 1];
    }
}
