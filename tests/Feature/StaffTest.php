<?php

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\OfficeType;
use AzahariZaman\BackOffice\Enums\StaffStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestStructure()
    {
        $company = Company::factory()->create([
            'name' => 'Test Company',
            'is_active' => true,
        ]);

        $officeType = OfficeType::factory()->create([
            'name' => 'Headquarters',
            'code' => 'HQ',
            'is_active' => true,
        ]);

        $office = Office::factory()->create([
            'name' => 'Main Office',
            'company_id' => $company->id,
            'office_type_id' => $officeType->id,
            'is_active' => true,
        ]);

        $department = Department::factory()->create([
            'name' => 'IT Department',
            'code' => 'IT',
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        return compact('company', 'office', 'department');
    }

    /** @test */
    public function it_can_create_staff()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'position' => 'Software Developer',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('backoffice_staff', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'position' => 'Software Developer',
            'department_id' => $structure['department']->id,
            'status' => 'active',
            'is_active' => true,
        ]);

        $this->assertEquals('John Doe', $staff->name);
        $this->assertEquals('john@example.com', $staff->email);
        $this->assertEquals($structure['department']->id, $staff->department_id);
    }

    /** @test */
    public function it_belongs_to_department()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'employee_id' => 'EMP002',
            'position' => 'Project Manager',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertEquals($structure['department']->id, $staff->department->id);
        $this->assertEquals('IT Department', $staff->department->name);
    }

    /** @test */
    public function it_can_have_supervisor_relationship()
    {
        $structure = $this->createTestStructure();

        $supervisor = Staff::factory()->create([
            'name' => 'Senior Manager',
            'email' => 'manager@example.com',
            'employee_id' => 'MGR001',
            'position' => 'Department Manager',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $subordinate = Staff::factory()->create([
            'name' => 'Junior Developer',
            'email' => 'junior@example.com',
            'employee_id' => 'JUN001',
            'position' => 'Junior Developer',
            'department_id' => $structure['department']->id,
            'supervisor_id' => $supervisor->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->assertEquals($supervisor->id, $subordinate->supervisor_id);
        $this->assertEquals($supervisor->id, $subordinate->supervisor->id);
        $this->assertTrue($supervisor->subordinates->contains($subordinate));
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'name' => 'Active Employee',
            'email' => 'active@example.com',
            'employee_id' => 'ACT001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $terminatedStaff = Staff::factory()->create([
            'name' => 'Terminated Employee',
            'email' => 'terminated@example.com',
            'employee_id' => 'TER001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::TERMINATED,
            'is_active' => false,
        ]);

        $activeStaffList = Staff::byStatus(StaffStatus::ACTIVE)->get();
        $terminatedStaffList = Staff::byStatus(StaffStatus::TERMINATED)->get();

        $this->assertCount(1, $activeStaffList);
        $this->assertCount(1, $terminatedStaffList);
        $this->assertTrue($activeStaffList->contains('id', $activeStaff->id));
        $this->assertTrue($terminatedStaffList->contains('id', $terminatedStaff->id));
    }

    /** @test */
    public function it_can_scope_by_department()
    {
        $structure = $this->createTestStructure();

        // Create second department
        $hrDepartment = Department::factory()->create([
            'name' => 'HR Department',
            'code' => 'HR',
            'office_id' => $structure['office']->id,
            'is_active' => true,
        ]);

        $itStaff = Staff::factory()->create([
            'name' => 'IT Staff',
            'email' => 'it@example.com',
            'employee_id' => 'IT001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $hrStaff = Staff::factory()->create([
            'name' => 'HR Staff',
            'email' => 'hr@example.com',
            'employee_id' => 'HR001',
            'department_id' => $hrDepartment->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $itDepartmentStaff = Staff::byDepartment($structure['department']->id)->get();
        
        $this->assertCount(1, $itDepartmentStaff);
        $this->assertTrue($itDepartmentStaff->contains('id', $itStaff->id));
        $this->assertFalse($itDepartmentStaff->contains('id', $hrStaff->id));
    }

    /** @test */
    public function it_can_scope_active_staff()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'name' => 'Active Staff',
            'email' => 'active@example.com',
            'employee_id' => 'ACT001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $inactiveStaff = Staff::factory()->create([
            'name' => 'Inactive Staff',
            'email' => 'inactive@example.com',
            'employee_id' => 'INA001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::INACTIVE,
            'is_active' => false,
        ]);

        $activeStaffList = Staff::active()->get();
        
        $this->assertCount(1, $activeStaffList);
        $this->assertTrue($activeStaffList->contains('id', $activeStaff->id));
        $this->assertFalse($activeStaffList->contains('id', $inactiveStaff->id));
    }

    /** @test */
    public function it_can_get_staff_hierarchy()
    {
        $structure = $this->createTestStructure();

        $manager = Staff::factory()->create([
            'name' => 'Department Manager',
            'email' => 'manager@example.com',
            'employee_id' => 'MGR001',
            'position' => 'Manager',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $teamLead = Staff::factory()->create([
            'name' => 'Team Lead',
            'email' => 'lead@example.com',
            'employee_id' => 'LEAD001',
            'position' => 'Team Lead',
            'department_id' => $structure['department']->id,
            'supervisor_id' => $manager->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $developer = Staff::factory()->create([
            'name' => 'Developer',
            'email' => 'dev@example.com',
            'employee_id' => 'DEV001',
            'position' => 'Developer',
            'department_id' => $structure['department']->id,
            'supervisor_id' => $teamLead->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        // Test supervisor relationships
        $this->assertEquals($manager->id, $teamLead->supervisor->id);
        $this->assertEquals($teamLead->id, $developer->supervisor->id);

        // Test subordinate relationships
        $this->assertTrue($manager->subordinates->contains($teamLead));
        $this->assertTrue($teamLead->subordinates->contains($developer));
        $this->assertCount(1, $manager->subordinates);
        $this->assertCount(1, $teamLead->subordinates);
        $this->assertCount(0, $developer->subordinates);
    }

    /** @test */
    public function it_validates_unique_employee_id()
    {
        $structure = $this->createTestStructure();

        Staff::factory()->create([
            'name' => 'First Employee',
            'email' => 'first@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Staff::factory()->create([
            'name' => 'Second Employee',
            'email' => 'second@example.com',
            'employee_id' => 'EMP001', // Same employee ID
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_validates_unique_email()
    {
        $structure = $this->createTestStructure();

        Staff::factory()->create([
            'name' => 'First Employee',
            'email' => 'employee@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Staff::factory()->create([
            'name' => 'Second Employee',
            'email' => 'employee@example.com', // Same email
            'employee_id' => 'EMP002',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_requires_name_email_and_department()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Staff::factory()->create([
            'employee_id' => 'EMP001',
            'status' => StaffStatus::ACTIVE,
        ]);
    }

    /** @test */
    public function it_defaults_to_active_status()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
        ]);

        $this->assertEquals(StaffStatus::ACTIVE->value, $staff->status->value);
        $this->assertTrue($staff->is_active);
    }
}