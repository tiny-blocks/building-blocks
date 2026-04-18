<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use InvalidArgumentException;

final class InvalidEventType extends InvalidArgumentException
{
    public function __construct(public readonly string $value, public readonly string $pattern)
    {
        parent::__construct(
            message: sprintf('Event type <%s> does not match the required pattern <%s>.', $value, $pattern)
        );
    }
}
