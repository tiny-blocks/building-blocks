<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Event;

use TinyBlocks\Collection\Collection;
use TinyBlocks\Mapper\ElementType;

/**
 * @extends Collection<EventRecord>
 */
#[ElementType(EventRecord::class)]
final class EventRecords extends Collection
{
}
