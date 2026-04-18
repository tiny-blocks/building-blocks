<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use RuntimeException;

final class MissingIdentityProperty extends RuntimeException
{
    public function __construct(public readonly string $propertyName, public readonly string $className)
    {
        parent::__construct(
            message: sprintf(
                'Property <%s> referenced by IDENTITY constant does not exist in <%s>.',
                $propertyName,
                $className
            )
        );
    }
}
