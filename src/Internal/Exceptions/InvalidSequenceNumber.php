<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use InvalidArgumentException;

final class InvalidSequenceNumber extends InvalidArgumentException
{
    public function __construct(public readonly int $value)
    {
        $template = 'Sequence number must be greater than or equal to 0, got <%d>.';

        parent::__construct(sprintf($template, $value));
    }
}
