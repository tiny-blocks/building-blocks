<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use InvalidArgumentException;

final class InvalidRevision extends InvalidArgumentException
{
    public function __construct(public readonly int $value)
    {
        $template = 'Revision must be greater than or equal to 1, got <%d>.';

        parent::__construct(message: sprintf($template, $value));
    }
}
