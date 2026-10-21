<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Upcast\SingleUpcasterBehavior;
use TinyBlocks\BuildingBlocks\Upcast\Upcaster;

final class ProductV1Upcaster implements Upcaster
{
    use SingleUpcasterBehavior;

    private const int TO_REVISION = 2;
    private const int FROM_REVISION = 1;
    private const string EXPECTED_EVENT_TYPE = 'ProductAdded';

    protected function rewrite(array $payload): array
    {
        return [...$payload, 'quantity' => 1];
    }
}
