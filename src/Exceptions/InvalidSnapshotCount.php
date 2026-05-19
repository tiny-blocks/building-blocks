<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use InvalidArgumentException;

final class InvalidSnapshotCount extends InvalidArgumentException
{
    public function __construct(public readonly int $count)
    {
        $template = 'Snapshot count must be at least 1, got <%d>.';

        parent::__construct(message: sprintf($template, $count));
    }
}
