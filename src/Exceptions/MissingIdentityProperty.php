<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use RuntimeException;

final class MissingIdentityProperty extends RuntimeException
{
    public function __construct(public readonly string $className, public readonly string $propertyName)
    {
        $template = 'Property <%s> referenced by identityName() does not exist in <%s>.';

        parent::__construct(message: sprintf($template, $propertyName, $className));
    }
}
