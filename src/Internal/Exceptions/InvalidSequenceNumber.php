<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use InvalidArgumentException;

final class InvalidSequenceNumber extends InvalidArgumentException
{
    public function __construct(public readonly int $value)
    {
        parent::__construct(
            message: sprintf('Sequence number must be greater than or equal to 0, got <%d>.', $value)
        );
    }
}
