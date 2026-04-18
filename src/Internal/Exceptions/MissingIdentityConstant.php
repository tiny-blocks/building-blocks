<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use RuntimeException;

final class MissingIdentityConstant extends RuntimeException
{
    public function __construct(public readonly string $className)
    {
        parent::__construct(message: sprintf('Constant IDENTITY is not defined in <%s>.', $className));
    }
}
