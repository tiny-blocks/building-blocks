<?php

declare(strict_types=1);

namespace TinyBlocks\BuildingBlocks\Entity;

use TinyBlocks\BuildingBlocks\Exceptions\MissingIdentityProperty;

trait EntityBehavior
{
    public function identity(): Identity
    {
        /** @var Identity $identity */
        $identity = $this->{$this->identityName()};

        return $identity;
    }

    public function identityName(): string
    {
        $name = $this->identityProperty();

        if (!property_exists($this, $name)) {
            throw new MissingIdentityProperty(className: static::class, propertyName: $name);
        }

        return $name;
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

    /**
     * Returns the property name that holds the entity's identity.
     *
     * <p>Defaults to <code>'id'</code>. Override in entities whose identity property has a different name.</p>
     *
     * @return string The property name backing the identity.
     */
    protected function identityProperty(): string
    {
        return 'id';
    }
}
