<?php

namespace AzahariZaman\BackOffice\Tests\Unit;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Enums\ActiveStatus;
use AzahariZaman\BackOffice\Enums\StaffStatus;

class EnumsTest extends TestCase
{
    /** @test */
    public function active_status_enum_has_correct_values()
    {
        $this->assertEquals(1, ActiveStatus::ACTIVE->value);
        $this->assertEquals(0, ActiveStatus::INACTIVE->value);
    }

    /** @test */
    public function staff_status_enum_has_correct_values()
    {
        $this->assertEquals('active', StaffStatus::ACTIVE->value);
        $this->assertEquals('inactive', StaffStatus::INACTIVE->value);
        $this->assertEquals('terminated', StaffStatus::TERMINATED->value);
        $this->assertEquals('suspended', StaffStatus::SUSPENDED->value);
        $this->assertEquals('on_leave', StaffStatus::ON_LEAVE->value);
    }

    /** @test */
    public function staff_status_enum_has_correct_labels()
    {
        $this->assertEquals('Active', StaffStatus::ACTIVE->label());
        $this->assertEquals('Inactive', StaffStatus::INACTIVE->label());
        $this->assertEquals('Terminated', StaffStatus::TERMINATED->label());
        $this->assertEquals('Suspended', StaffStatus::SUSPENDED->label());
        $this->assertEquals('On Leave', StaffStatus::ON_LEAVE->label());
    }

    /** @test */
    public function staff_status_can_get_all_values()
    {
        $values = StaffStatus::values();
        
        $this->assertCount(5, $values);
        $this->assertContains('active', $values);
        $this->assertContains('inactive', $values);
        $this->assertContains('terminated', $values);
        $this->assertContains('suspended', $values);
        $this->assertContains('on_leave', $values);
    }

    /** @test */
    public function staff_status_can_get_all_options()
    {
        $options = StaffStatus::options();
        
        $this->assertCount(5, $options);
        $this->assertEquals('Active', $options['active']);
        $this->assertEquals('Inactive', $options['inactive']);
        $this->assertEquals('Terminated', $options['terminated']);
        $this->assertEquals('Suspended', $options['suspended']);
        $this->assertEquals('On Leave', $options['on_leave']);
    }
}