<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use InvalidArgumentException;

final class InvalidUuid extends InvalidArgumentException
{
    public function __construct(public readonly string $value)
    {
        $template = 'Value <%s> is not a valid UUID.';

        parent::__construct(message: sprintf($template, $value));
    }
}
