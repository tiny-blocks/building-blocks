<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use InvalidArgumentException;

final class InvalidRevision extends InvalidArgumentException
{
    public function __construct(public readonly int $value)
    {
        parent::__construct(
            sprintf('Revision must be greater than or equal to 1, got <%d>.', $value)
        );
    }
}
