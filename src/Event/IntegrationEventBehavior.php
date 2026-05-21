<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

trait IntegrationEventBehavior
{
    public function revision(): Revision
    {
        return Revision::initial();
    }
}
