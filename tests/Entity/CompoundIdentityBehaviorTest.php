<?php

declare(strict_types=1);

namespace Test\TinyBlocks\BuildingBlocks\Entity;

use PHPUnit\Framework\TestCase;
use Test\TinyBlocks\BuildingBlocks\Models\AppointmentId;

final class CompoundIdentityBehaviorTest extends TestCase
{
    public function testIdentityValueReturnsAllFieldsAsAssociativeArray(): void
    {
        /** @Given a compound identity with two fields */
        $appointmentId = new AppointmentId(tenantId: 'tenant-1', appointmentId: 'apt-1');

        /** @When retrieving the identity value */
        $value = $appointmentId->identityValue();

        /** @Then both fields are returned in an associative array */
        self::assertSame(['tenantId' => 'tenant-1', 'appointmentId' => 'apt-1'], $value);
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
}
