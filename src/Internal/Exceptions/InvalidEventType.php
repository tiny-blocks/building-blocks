<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use InvalidArgumentException;

final class InvalidEventType extends InvalidArgumentException
{
    public function __construct(public readonly string $value, public readonly string $pattern)
    {
        $template = 'Event type <%s> does not match the required pattern <%s>.';

        parent::__construct(sprintf($template, $value, $pattern));
    }
}
