<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\AppointmentId;
use Test\TinyBlocks\BuildingBlocks\Models\AppointmentSlot;
use TinyBlocks\BuildingBlocks\Event\Revision;

final class CompoundIdentityBehaviorTest extends TestCase
{
    public function testEqualsReturnsFalseWhenTenantDiffers(): void
    {
        /** @Given two compound identities differing on the tenant */
        $first = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @And a counterpart with a different tenant */
        $second = new AppointmentId(tenantId: 'tenant-2', appointmentId: 'apt-1');

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenAppointmentDiffers(): void
    {
        /** @Given two compound identities differing on the appointment */
        $first = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @And a counterpart with a different appointment */
        $second = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-2');

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenObjectFieldDiffers(): void
    {
        /** @Given a compound identity */
        $first = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-1', practitionerId: 42);

        /** @And a counterpart differing only on the object-typed field */
        $second = new AppointmentSlot(revision: Revision::of(value: 8), tenantId: 'tenant-1', practitionerId: 42);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenStringFieldDiffers(): void
    {
        /** @Given a compound identity */
        $first = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-1', practitionerId: 42);

        /** @And a counterpart differing only on the string field */
        $second = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-2', practitionerId: 42);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsFalseWhenIntegerFieldDiffers(): void
    {
        /** @Given a compound identity */
        $first = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-1', practitionerId: 42);

        /** @And a counterpart differing only on the integer field */
        $second = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-1', practitionerId: 43);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are not equal */
        self::assertFalse($areEqual);
    }

    public function testEqualsReturnsTrueForIdenticalCompoundIdentities(): void
    {
        /** @Given two compound identities with identical field values */
        $first = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @And a matching counterpart */
        $second = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are considered equal */
        self::assertTrue($areEqual);
    }

    public function testIdentityValueReturnsAllFieldsAsAssociativeArray(): void
    {
        /** @Given a compound identity with two fields */
        $appointmentId = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @When retrieving the identity value */
        $identityValue = $appointmentId->identityValue();

        /** @Then both fields are returned in an associative array */
        self::assertSame(['tenantId' => 'tenant-1', 'appointmentId' => 'apt-1'], $identityValue);
    }

    public function testIdentityValueReturnsFieldsPreservingScalarAndObjectTypes(): void
    {
        /** @Given a revision for the slot */
        $revision = Revision::of(value: 7);

        /** @And a compound identity with mixed scalar and object fields */
        $slot = new AppointmentSlot(revision: $revision, tenantId: 'tenant-1', practitionerId: 42);

        /** @When retrieving the identity value */
        $identityValue = $slot->identityValue();

        /** @Then each field is present preserving its original scalar and object types */
        self::assertSame(
            ['revision' => $revision, 'tenantId' => 'tenant-1', 'practitionerId' => 42],
            $identityValue
        );
    }

    public function testEqualsReturnsTrueForCompoundIdentitiesWithIdenticalMixedFields(): void
    {
        /** @Given a compound identity with mixed types */
        $first = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-1', practitionerId: 42);

        /** @And a counterpart with identical field values */
        $second = new AppointmentSlot(revision: Revision::of(value: 7), tenantId: 'tenant-1', practitionerId: 42);

        /** @When comparing them */
        $areEqual = $first->equals(other: $second);

        /** @Then they are considered equal */
        self::assertTrue($areEqual);
    }
}
