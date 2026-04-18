<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityConstant;
use TinyBlocks\BuildingBlocks\Internal\Exceptions\MissingIdentityProperty;

trait EntityBehavior
{
    public function getIdentityName(): string
    {
        if (!defined('static::IDENTITY')) {
            throw new MissingIdentityConstant(className: static::class);
        }

        $name = static::IDENTITY;

        if (!property_exists($this, $name)) {
            throw new MissingIdentityProperty(propertyName: $name, className: static::class);
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
        return $this->getIdentity() == $other;
    }
}
