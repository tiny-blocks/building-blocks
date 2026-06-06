<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use InvalidArgumentException;

final class InvalidUtc extends InvalidArgumentException
{
    public function __construct(public readonly string $value)
    {
        $template = 'Value <%s> is not a valid ISO 8601 instant.';

        parent::__construct(message: sprintf($template, $value));
    }
}
