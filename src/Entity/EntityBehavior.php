<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityProperty;

trait EntityBehavior
{
    public function identityName(): string
    {
        $name = $this->identityProperty();

        if (!property_exists($this, $name)) {
            throw new MissingIdentityProperty(className: static::class, propertyName: $name);
        }

        return $name;
    }

    protected function identityProperty(): string
    {
        return 'id';
    }

    public function identity(): Identity
    {
        return $this->{$this->identityName()};
    }

    public function identityValue(): mixed
    {
        return $this->identity()->identityValue();
    }

    public function sameIdentityOf(Entity $other): bool
    {
        return $this->identityEquals(other: $other->identity());
    }

    public function identityEquals(Identity $other): bool
    {
        return $this->identity()->equals(other: $other);
    }
}
