<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Exceptions;

use LogicException;

final class EventHandlerMethodNotFound extends LogicException
{
    public function __construct(public readonly string $methodName, public readonly string $aggregateClass)
    {
        $template = 'Handler method <%s> not found in aggregate <%s>.';

        parent::__construct(message: sprintf($template, $methodName, $aggregateClass));
    }
}
