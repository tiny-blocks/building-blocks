<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks;

use TinyBlocks\BuildingBlocks\Exceptions\InvalidUtc;
use TinyBlocks\Time\Exceptions\InvalidInstant;
use TinyBlocks\Time\Instant;
use TinyBlocks\Vo\ValueObject;
use TinyBlocks\Vo\ValueObjectBehavior;

/**
 * A single point on the timeline in UTC, compared by the moment it represents.
 *
 * <p>Normalized to UTC with second precision so that two values representing the same point in
 * time are equal regardless of how they were created.</p>
 */
final readonly class Utc implements ValueObject
{
    use ValueObjectBehavior;

    private function __construct(private string $iso)
    {
    }

    /**
     * Creates a UTC representing the current moment.
     *
     * @return Utc The current moment in UTC.
     */
    public static function now(): Utc
    {
        return new Utc(iso: Instant::now()->toIso8601());
    }

    /**
     * Creates a UTC from an ISO 8601 representation.
     *
     * @param string $value An ISO 8601 date-time string (e.g. 2026-02-17T10:30:00+00:00).
     * @return Utc The created moment in UTC.
     * @throws InvalidUtc If the value is not a valid ISO 8601 instant.
     */
    public static function fromIso8601(string $value): Utc
    {
        try {
            return new Utc(iso: Instant::fromString(value: $value)->toIso8601());
        } catch (InvalidInstant) {
            throw new InvalidUtc(value: $value);
        }
    }

    /**
     * Returns the UTC as an ISO 8601 string with second precision.
     *
     * @return string The ISO 8601 representation in UTC.
     */
    public function toIso8601(): string
    {
        return $this->iso;
    }
}
