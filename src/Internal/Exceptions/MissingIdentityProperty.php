<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use RuntimeException;

final class MissingIdentityProperty extends RuntimeException
{
    public function __construct(public readonly string $className, public readonly string $propertyName)
    {
        $template = 'Property <%s> referenced by identityName() does not exist in <%s>.';

        parent::__construct(sprintf($template, $propertyName, $className));
    }
}
