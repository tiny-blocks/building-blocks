<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityProperty;

trait EntityBehavior
{
    abstract protected function identityName(): string;

    public function getIdentityName(): string
    {
        $name = $this->identityName();

        if (!property_exists($this, $name)) {
            throw new MissingIdentityProperty(className: static::class, propertyName: $name);
        }

        return $name;
    }

    public function getIdentity(): Identity
    {
        return $this->{$this->getIdentityName()};
    }

    public function getIdentityValue(): mixed
    {
        return $this->getIdentity()->getIdentityValue();
    }

    public function sameIdentityOf(Entity $other): bool
    {
        return $this->identityEquals(other: $other->getIdentity());
    }

    public function identityEquals(Identity $other): bool
    {
        return $this->getIdentity()->equals(other: $other);
    }
}
