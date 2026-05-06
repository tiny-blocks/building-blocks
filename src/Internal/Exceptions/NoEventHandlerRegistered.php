<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Internal\Exceptions;

use LogicException;

final class NoEventHandlerRegistered extends LogicException
{
    public function __construct(public readonly string $eventClass, public readonly string $aggregateClass)
    {
        $template = 'No handler registered for event <%s> in aggregate <%s>.';

        parent::__construct(sprintf($template, $eventClass, $aggregateClass));
    }
}
