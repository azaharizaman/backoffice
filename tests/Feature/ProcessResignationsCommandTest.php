<?php

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Tests\TestCase;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\OfficeType;
use AzahariZaman\BackOffice\Enums\StaffStatus;
use AzahariZaman\BackOffice\Commands\ProcessResignationsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ProcessResignationsCommandTest extends TestCase
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
    public function it_processes_due_resignations()
    {
        $structure = $this->createTestStructure();

        // Create staff with resignation due yesterday
        $dueStaff = Staff::factory()->create([
            'name' => 'Due Staff',
            'email' => 'due@example.com',
            'employee_id' => 'EMP001',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Time to go',
            'is_active' => true,
        ]);

        // Create staff with resignation in future
        $futureStaff = Staff::factory()->create([
            'name' => 'Future Staff',
            'email' => 'future@example.com',
            'employee_id' => 'EMP002',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->addDays(5),
            'resignation_reason' => 'Future resignation',
            'is_active' => true,
        ]);

        // Run the command with force flag
        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->assertExitCode(0);

        // Check that due staff was processed
        $dueStaff->refresh();
        $this->assertEquals(StaffStatus::RESIGNED, $dueStaff->status);
        $this->assertNotNull($dueStaff->resigned_at);
        $this->assertFalse($dueStaff->is_active);

        // Check that future staff was not processed
        $futureStaff->refresh();
        $this->assertEquals(StaffStatus::ACTIVE, $futureStaff->status);
        $this->assertNull($futureStaff->resigned_at);
        $this->assertTrue($futureStaff->is_active);
    }

    /** @test */
    public function it_handles_no_resignations_to_process()
    {
        $structure = $this->createTestStructure();

        // Create staff with no resignations scheduled
        Staff::factory()->create([
            'name' => 'Active Staff',
            'email' => 'active@example.com',
            'employee_id' => 'EMP003',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'is_active' => true,
        ]);

        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->expectsOutput('No resignations to process.')
             ->assertExitCode(0);
    }

    /** @test */
    public function it_supports_dry_run_mode()
    {
        $structure = $this->createTestStructure();

        $dueStaff = Staff::factory()->create([
            'name' => 'Due Staff',
            'email' => 'due@example.com',
            'employee_id' => 'EMP004',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'is_active' => true,
        ]);

        $this->artisan('backoffice:process-resignations', ['--dry-run' => true])
             ->expectsOutput('Dry run mode - no changes will be made.')
             ->assertExitCode(0);

        // Staff should not be processed in dry-run mode
        $dueStaff->refresh();
        $this->assertEquals(StaffStatus::ACTIVE, $dueStaff->status);
        $this->assertNull($dueStaff->resigned_at);
        $this->assertTrue($dueStaff->is_active);
    }

    /** @test */
    public function it_displays_resignation_information_before_processing()
    {
        $structure = $this->createTestStructure();

        $dueStaff = Staff::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'employee_id' => 'EMP005',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'resignation_reason' => 'Better opportunity',
            'is_active' => true,
        ]);

        $this->artisan('backoffice:process-resignations', ['--dry-run' => true])
             ->expectsOutput('Found 1 resignation(s) to process:')
             ->expectsOutput('EMP005')
             ->expectsOutput('John Doe')
             ->expectsOutput('IT Department')
             ->assertExitCode(0);
    }

    /** @test */
    public function it_processes_multiple_resignations()
    {
        $structure = $this->createTestStructure();

        // Create multiple staff with due resignations
        $staff1 = Staff::factory()->create([
            'name' => 'Staff One',
            'email' => 'one@example.com',
            'employee_id' => 'EMP006',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(2),
            'is_active' => true,
        ]);

        $staff2 = Staff::factory()->create([
            'name' => 'Staff Two',
            'email' => 'two@example.com',
            'employee_id' => 'EMP007',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->subDays(1),
            'is_active' => true,
        ]);

        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->expectsOutput('Found 2 resignation(s) to process:')
             ->expectsOutput('Processed: 2')
             ->assertExitCode(0);

        // Both staff should be processed
        $staff1->refresh();
        $staff2->refresh();
        
        $this->assertEquals(StaffStatus::RESIGNED, $staff1->status);
        $this->assertEquals(StaffStatus::RESIGNED, $staff2->status);
        $this->assertNotNull($staff1->resigned_at);
        $this->assertNotNull($staff2->resigned_at);
    }

    /** @test */
    public function it_processes_resignations_due_today()
    {
        $structure = $this->createTestStructure();

        $todayStaff = Staff::factory()->create([
            'name' => 'Today Staff',
            'email' => 'today@example.com',
            'employee_id' => 'EMP008',
            'department_id' => $structure['department']->id,
            'status' => StaffStatus::ACTIVE,
            'resignation_date' => Carbon::now()->startOfDay(),
            'is_active' => true,
        ]);

        $this->artisan('backoffice:process-resignations', ['--force' => true])
             ->assertExitCode(0);

        $todayStaff->refresh();
        $this->assertEquals(StaffStatus::RESIGNED, $todayStaff->status);
        $this->assertNotNull($todayStaff->resigned_at);
    }
}