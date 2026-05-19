<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use InvalidArgumentException;

final class InvalidAggregateVersion extends InvalidArgumentException
{
    public function __construct(public readonly int $value)
    {
        $template = 'Aggregate version must be greater than or equal to 0, got <%d>.';

        parent::__construct(message: sprintf($template, $value));
    }
}
