<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use RuntimeException;

final class IncompleteAggregateState extends RuntimeException
{
    public function __construct(public readonly string $className, public readonly array $propertyNames)
    {
        $template = 'Strict reconstitution of <%s> left required properties uninitialized: <%s>.';

        parent::__construct(message: sprintf($template, $className, implode(', ', $propertyNames)));
    }
}
