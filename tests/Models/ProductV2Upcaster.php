<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Models;

use TinyBlocks\BuildingBlocks\Upcast\SingleUpcasterBehavior;
use TinyBlocks\BuildingBlocks\Upcast\Upcaster;

final class ProductV2Upcaster implements Upcaster
{
    use SingleUpcasterBehavior;

    private const string EXPECTED_EVENT_TYPE = 'ProductAdded';
    private const int FROM_REVISION = 2;
    private const int TO_REVISION = 3;

    protected function doUpcast(array $data): array
    {
        return [...$data, 'notes' => ''];
    }
}
