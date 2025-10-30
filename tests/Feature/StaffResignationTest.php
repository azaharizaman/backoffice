<?php

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\OfficeType;
use AzahariZaman\BackOffice\Enums\StaffStatus;
use AzahariZaman\BackOffice\Exceptions\InvalidResignationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class StaffResignationTest extends TestCase
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
    public function it_can_schedule_staff_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $resignationDate = Carbon::now()->addDays(30);
        $resignationReason = 'Better opportunity elsewhere';

        $staff->scheduleResignation($resignationDate, $resignationReason);

        $this->assertDatabaseHas('backoffice_staff', [
            'id' => $staff->id,
            'resignation_date' => $resignationDate->toDateString(),
            'resignation_reason' => $resignationReason,
            'status' => StaffStatus::ACTIVE->value,
            'resigned_at' => null,
        ]);

        $this->assertTrue($staff->fresh()->hasPendingResignation());
    }

    /** @test */
    public function it_can_process_staff_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'employee_id' => 'EMP002',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Personal reasons',
            'is_active' => true,
        ]);

        $staff->processResignation();

        $freshStaff = $staff->fresh();
        $this->assertEquals(StaffStatus::RESIGNED, $freshStaff->status);
        $this->assertNotNull($freshStaff->resigned_at);
        $this->assertFalse($freshStaff->is_active);
        $this->assertTrue($freshStaff->isResigned());
    }

    /** @test */
    public function it_can_cancel_scheduled_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'employee_id' => 'EMP003',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(15),
            'resignation_reason' => 'Counter offer accepted',
            'is_active' => true,
        ]);

        $this->assertTrue($staff->hasPendingResignation());

        $staff->cancelResignation();

        $freshStaff = $staff->fresh();
        $this->assertNull($freshStaff->resignation_date);
        $this->assertNull($freshStaff->resignation_reason);
        $this->assertFalse($freshStaff->hasPendingResignation());
    }

    /** @test */
    public function it_can_check_if_resignation_is_due()
    {
        $structure = $this->createTestStructure();

        // Staff with resignation due yesterday
        $staffPastDue = Staff::factory()->create([
            'name' => 'Past Due Staff',
            'email' => 'pastdue@example.com',
            'employee_id' => 'EMP004',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'is_active' => true,
        ]);

        // Staff with resignation due in future
        $staffFuture = Staff::factory()->create([
            'name' => 'Future Staff',
            'email' => 'future@example.com',
            'employee_id' => 'EMP005',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(5),
            'is_active' => true,
        ]);

        $this->assertTrue($staffPastDue->isResignationDue());
        $this->assertFalse($staffFuture->isResignationDue());
    }

    /** @test */
    public function it_can_get_days_until_resignation()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Future Resignation',
            'email' => 'future@example.com',
            'employee_id' => 'EMP006',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(10),
            'is_active' => true,
        ]);

        $daysUntil = $staff->getDaysUntilResignation();
        $this->assertEquals(10, $daysUntil);
    }

    /** @test */
    public function it_can_scope_staff_with_pending_resignations()
    {
        $structure = $this->createTestStructure();

        $activStaff = Staff::factory()->create([
            'name' => 'Active Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP007',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $pendingResignationStaff = Staff::factory()->create([
            'name' => 'Pending Resignation',
            'email' => 'pending@example.com',
            'employee_id' => 'EMP008',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(5),
            'is_active' => true,
        ]);

        $resignedStaff = Staff::factory()->create([
            'name' => 'Already Resigned',
            'email' => 'resigned@example.com',
            'employee_id' => 'EMP009',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'resignation_date' => Carbon::now()->subDays(5),
            'resigned_at' => Carbon::now()->subDays(5),
            'is_active' => false,
        ]);

        $pendingStaff = Staff::pendingResignation()->get();
        
        $this->assertCount(1, $pendingStaff);
        $this->assertTrue($pendingStaff->contains('id', $pendingResignationStaff->id));
        $this->assertFalse($pendingStaff->contains('id', $activStaff->id));
        $this->assertFalse($pendingStaff->contains('id', $resignedStaff->id));
    }

    /** @test */
    public function it_can_scope_resigned_staff()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'name' => 'Active Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP010',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $resignedStaff = Staff::factory()->create([
            'name' => 'Resigned Staff',
            'email' => 'resigned@example.com',
            'employee_id' => 'EMP011',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'resigned_at' => Carbon::now()->subDays(5),
            'is_active' => false,
        ]);

        $resigned = Staff::resigned()->get();
        
        $this->assertCount(1, $resigned);
        $this->assertTrue($resigned->contains('id', $resignedStaff->id));
        $this->assertFalse($resigned->contains('id', $activeStaff->id));
    }

    /** @test */
    public function it_validates_resignation_date_not_in_past_for_new_staff()
    {
        $structure = $this->createTestStructure();

        $this->expectException(InvalidResignationException::class);
        $this->expectExceptionMessage('Resignation date cannot be in the past for new staff entries.');

        Staff::factory()->create([
            'name' => 'Invalid Staff',
            'email' => 'invalid@example.com',
            'employee_id' => 'EMP012',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_automatically_sets_resigned_at_when_status_changes_to_resigned()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Auto Resigned',
            'email' => 'auto@example.com',
            'employee_id' => 'EMP013',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $staff->update(['status' => StaffStatus::RESIGNED]);

        $freshStaff = $staff->fresh();
        $this->assertEquals(StaffStatus::RESIGNED, $freshStaff->status);
        $this->assertNotNull($freshStaff->resigned_at);
        $this->assertFalse($freshStaff->is_active);
    }

    /** @test */
    public function it_clears_resignation_data_when_reactivating_resigned_staff()
    {
        $structure = $this->createTestStructure();

        $staff = Staff::factory()->create([
            'name' => 'Reactivated Staff',
            'email' => 'reactivated@example.com',
            'employee_id' => 'EMP014',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'resignation_date' => Carbon::now()->subDays(10),
            'resignation_reason' => 'Previous job',
            'resigned_at' => Carbon::now()->subDays(10),
            'is_active' => false,
        ]);

        $staff->update(['status' => StaffStatus::ACTIVE]);

        $freshStaff = $staff->fresh();
        $this->assertEquals(StaffStatus::ACTIVE, $freshStaff->status);
        $this->assertNull($freshStaff->resignation_date);
        $this->assertNull($freshStaff->resignation_reason);
        $this->assertNull($freshStaff->resigned_at);
        $this->assertTrue($freshStaff->is_active);
    }

    /** @test */
    public function it_can_filter_staff_by_status()
    {
        $structure = $this->createTestStructure();

        $activeStaff = Staff::factory()->create([
            'name' => 'Active Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP015',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $resignedStaff = Staff::factory()->create([
            'name' => 'Resigned Staff',
            'email' => 'resigned@example.com',
            'employee_id' => 'EMP016',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::RESIGNED,
            'is_active' => false,
        ]);

        $activeList = Staff::byStatus(StaffStatus::ACTIVE)->get();
        $resignedList = Staff::byStatus(StaffStatus::RESIGNED)->get();

        $this->assertCount(1, $activeList);
        $this->assertCount(1, $resignedList);
        $this->assertTrue($activeList->contains('id', $activeStaff->id));
        $this->assertTrue($resignedList->contains('id', $resignedStaff->id));
    }
}