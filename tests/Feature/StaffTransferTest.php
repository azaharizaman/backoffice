<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Tests\Feature;

use AzahariZaman\BackOffice\Enums\StaffStatus;
use AzahariZaman\BackOffice\Enums\StaffTransferStatus;
use AzahariZaman\BackOffice\Exceptions\InvalidTransferException;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\StaffTransfer;
use AzahariZaman\BackOffice\Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StaffTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Office $sourceOffice;
    protected Office $targetOffice;
    protected Department $sourceDepartment;
    protected Department $targetDepartment;
    protected Staff $staff;
    protected Staff $supervisor;
    protected Staff $newSupervisor;
    protected Staff $hrStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Test Company',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $this->sourceOffice = Office::create([
            'name' => 'Source Office',
            'code' => 'SRC',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->targetOffice = Office::create([
            'name' => 'Target Office',
            'code' => 'TGT',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->sourceDepartment = Department::create([
            'name' => 'Source Department',
            'code' => 'SRC_DEPT',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->targetDepartment = Department::create([
            'name' => 'Target Department',
            'code' => 'TGT_DEPT',
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->supervisor = Staff::create([
            'employee_id' => 'SUP001',
            'first_name' => 'Current',
            'last_name' => 'Supervisor',
            'email' => 'supervisor@test.com',
            'company_id' => $this->company->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);

        $this->newSupervisor = Staff::create([
            'employee_id' => 'SUP002',
            'first_name' => 'New',
            'last_name' => 'Supervisor',
            'email' => 'newsupervisor@test.com',
            'company_id' => $this->company->id,
            'office_id' => $this->targetOffice->id,
            'is_active' => true,
        ]);

        $this->staff = Staff::create([
            'employee_id' => 'EMP001',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'employee@test.com',
            'company_id' => $this->company->id,
            'office_id' => $this->sourceOffice->id,
            'department_id' => $this->sourceDepartment->id,
            'supervisor_id' => $this->supervisor->id,
            'is_active' => true,
        ]);

        $this->hrStaff = Staff::create([
            'employee_id' => 'HR001',
            'first_name' => 'HR',
            'last_name' => 'Manager',
            'email' => 'hr@test.com',
            'company_id' => $this->company->id,
            'office_id' => $this->sourceOffice->id,
            'is_active' => true,
        ]);
    }

    protected function createTransfer(array $attributes = []): StaffTransfer
    {
        return StaffTransfer::create(array_merge([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'effective_date' => now()->addWeek(),
            'reason' => 'Test transfer',
            'status' => StaffTransferStatus::PENDING,
            'requested_by' => $this->staff->id,
            'requested_at' => now(),
        ], $attributes));
    }

    public function test_it_can_create_immediate_transfer_request(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'current_department_id' => $this->sourceDepartment->id,
            'new_department_id' => $this->targetDepartment->id,
            'current_supervisor_id' => $this->supervisor->id,
            'new_supervisor_id' => $this->newSupervisor->id,
            'effective_date' => now(),
            'reason' => 'Immediate transfer for project requirements',
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->assertInstanceOf(StaffTransfer::class, $transfer);
        $this->assertEquals(StaffTransferStatus::PENDING, $transfer->status);
        $this->assertEquals($this->staff->id, $transfer->staff_id);
        $this->assertEquals($this->targetOffice->id, $transfer->new_office_id);
        $this->assertTrue($transfer->effective_date->isToday());
    }

    public function test_it_can_create_scheduled_transfer_request(): void
    {
        $futureDate = now()->addMonth();

        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'effective_date' => $futureDate,
            'reason' => 'Scheduled transfer for next month',
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->assertEquals($futureDate->format('Y-m-d'), $transfer->effective_date->format('Y-m-d'));
        $this->assertFalse($transfer->isImmediate());
    }

    public function test_it_can_approve_transfer_request(): void
    {
        $transfer = $this->createTransfer([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve($this->hrStaff, 'HR Department approval');

        $this->assertEquals(StaffTransferStatus::APPROVED, $transfer->fresh()->status);
        $this->assertEquals($this->hrStaff->id, $transfer->fresh()->approved_by);
        $this->assertNotNull($transfer->fresh()->approved_at);
    }

    public function test_it_can_reject_transfer_request(): void
    {
        $transfer = $this->createTransfer([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->reject($this->hrStaff, 'Insufficient budget for transfer');

        $this->assertEquals(StaffTransferStatus::REJECTED, $transfer->fresh()->status);
        $this->assertEquals('Insufficient budget for transfer', $transfer->fresh()->rejection_reason);
        $this->assertNotNull($transfer->fresh()->rejected_at);
    }

    public function test_it_can_cancel_transfer_request(): void
    {
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $result = $transfer->cancel('Employee request');

        $this->assertTrue($result);
        $this->assertEquals(StaffTransferStatus::CANCELLED, $transfer->fresh()->status);
        $this->assertEquals('Employee request', $transfer->fresh()->cancellation_reason);
        $this->assertNotNull($transfer->fresh()->cancelled_at);
    }

    public function test_it_processes_immediate_transfer_automatically_when_approved(): void
    {
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'current_department_id' => $this->sourceDepartment->id,
            'new_department_id' => $this->targetDepartment->id,
            'current_supervisor_id' => $this->supervisor->id,
            'new_supervisor_id' => $this->newSupervisor->id,
            'effective_date' => now(),
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve('Immediate processing required');

        // Refresh models to see changes
        $this->staff->refresh();
        $transfer->refresh();

        // Check if transfer was completed automatically
        $this->assertEquals(StaffTransferStatus::COMPLETED, $transfer->status);
        $this->assertNotNull($transfer->completed_at);

        // Check if staff record was updated
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($this->targetDepartment->id, $this->staff->department_id);
        $this->assertEquals($this->newSupervisor->id, $this->staff->supervisor_id);
    }

    public function test_it_does_not_process_scheduled_transfer_automatically(): void
    {
        $futureDate = now()->addMonth();

        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'effective_date' => $futureDate,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve('Scheduled for next month');

        // Refresh models
        $this->staff->refresh();
        $transfer->refresh();

        // Transfer should be approved but not completed
        $this->assertEquals(StaffTransferStatus::APPROVED, $transfer->status);
        $this->assertNull($transfer->completed_at);

        // Staff record should not be updated yet
        $this->assertEquals($this->sourceOffice->id, $this->staff->office_id);
    }

    public function test_it_validates_against_same_office_transfer(): void
    {
        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Cannot transfer staff to the same office');

        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->sourceOffice->id, // Same office
            'effective_date' => now(),
            'reason' => 'Invalid transfer',
            'status' => StaffTransferStatus::PENDING,
        ]);
    }

    public function test_it_validates_against_pending_transfer_exists(): void
    {
        // Create first transfer
        StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Staff already has a pending transfer request');

        // Try to create second transfer
        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'effective_date' => now(),
            'reason' => 'Duplicate transfer',
            'status' => StaffTransferStatus::PENDING,
        ]);
    }

    public function test_it_validates_against_circular_supervisor_reference(): void
    {
        // Create a subordinate
        $subordinate = Staff::factory()->for($this->company)->for($this->sourceOffice)->create([
            'supervisor_id' => $this->staff->id,
        ]);

        $this->expectException(InvalidTransferException::class);
        $this->expectExceptionMessage('Cannot set supervisor: would create circular reference');

        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'current_supervisor_id' => $this->supervisor->id,
            'new_supervisor_id' => $subordinate->id, // Circular reference
            'effective_date' => now(),
            'reason' => 'Invalid supervisor assignment',
            'status' => StaffTransferStatus::PENDING,
        ]);
    }

    public function test_it_can_transfer_without_changing_supervisor(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'current_supervisor_id' => $this->supervisor->id,
            'new_supervisor_id' => $this->supervisor->id, // Same supervisor
            'effective_date' => now(),
            'reason' => 'Office change only',
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve('Approved');

        $this->staff->refresh();
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($this->supervisor->id, $this->staff->supervisor_id);
    }

    public function test_it_can_remove_supervisor_during_transfer(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'current_supervisor_id' => $this->supervisor->id,
            'new_supervisor_id' => null, // Remove supervisor
            'effective_date' => now(),
            'reason' => 'Promote to manager',
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve('Promotion approved');

        $this->staff->refresh();
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertNull($this->staff->supervisor_id);
    }

    public function test_it_can_only_transfer_office_without_department_change(): void
    {
        $transfer = StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            // No department change specified
            'effective_date' => now(),
            'reason' => 'Office relocation',
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfer->approve('Approved');

        $this->staff->refresh();
        $this->assertEquals($this->targetOffice->id, $this->staff->office_id);
        $this->assertEquals($this->sourceDepartment->id, $this->staff->department_id); // Unchanged
    }

    public function test_it_tracks_transfer_history_correctly(): void
    {
        // Create multiple transfers
        $transfer1 = StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::COMPLETED,
            'completed_at' => now()->subMonth(),
        ]);

        $transfer2 = StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->targetOffice->id,
            'new_office_id' => $this->sourceOffice->id,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $transfers = $this->staff->transfers()->orderBy('created_at')->get();

        $this->assertCount(2, $transfers);
        $this->assertEquals($transfer1->id, $transfers->first()->id);
        $this->assertEquals($transfer2->id, $transfers->last()->id);
    }

    public function test_it_can_check_if_staff_has_active_transfer(): void
    {
        // No active transfer initially
        $this->assertFalse($this->staff->hasActiveTransfer());

        // Create pending transfer
        StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->assertTrue($this->staff->hasActiveTransfer());

        // Create approved transfer
        StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::APPROVED,
        ]);

        $this->assertTrue($this->staff->hasActiveTransfer());
    }

    public function test_it_can_check_if_staff_can_be_transferred(): void
    {
        // Active staff can be transferred
        $this->assertTrue($this->staff->canBeTransferred());

        // Resigned staff cannot be transferred
        $this->staff->update(['status' => StaffStatus::RESIGNED]);
        $this->assertFalse($this->staff->canBeTransferred());

        // Staff with pending transfer cannot have another transfer
        $this->staff->update(['status' => StaffStatus::ACTIVE]);
        StaffTransfer::factory()->for($this->staff)->create([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $this->assertFalse($this->staff->canBeTransferred());
    }

    public function test_it_provides_transfer_scopes(): void
    {
        $pending = StaffTransfer::factory()->for($this->staff)->create([
            'status' => StaffTransferStatus::PENDING,
        ]);

        $approved = StaffTransfer::factory()->for($this->staff)->create([
            'status' => StaffTransferStatus::APPROVED,
        ]);

        $completed = StaffTransfer::factory()->for($this->staff)->create([
            'status' => StaffTransferStatus::COMPLETED,
        ]);

        $rejected = StaffTransfer::factory()->for($this->staff)->create([
            'status' => StaffTransferStatus::REJECTED,
        ]);

        // Test pending scope
        $pendingTransfers = StaffTransfer::pending()->get();
        $this->assertCount(1, $pendingTransfers);
        $this->assertEquals($pending->id, $pendingTransfers->first()->id);

        // Test approved scope
        $approvedTransfers = StaffTransfer::approved()->get();
        $this->assertCount(1, $approvedTransfers);
        $this->assertEquals($approved->id, $approvedTransfers->first()->id);

        // Test active scope (pending + approved)
        $activeTransfers = StaffTransfer::active()->get();
        $this->assertCount(2, $activeTransfers);

        // Test due scope (approved transfers due for processing)
        $dueTransfers = StaffTransfer::due()->get();
        $this->assertCount(1, $dueTransfers);
        $this->assertEquals($approved->id, $dueTransfers->first()->id);
    }

    public function test_it_handles_transfer_request_helper_method(): void
    {
        $requestedBy = Staff::factory()->for($this->company)->for($this->sourceOffice)->create();

        $transferData = $this->staff->requestTransfer(
            toOffice: $this->targetOffice,
            requestedBy: $requestedBy,
            effectiveDate: now()->addWeek(),
            toDepartment: $this->targetDepartment,
            toSupervisor: $this->newSupervisor,
            reason: 'Career development opportunity'
        );

        $this->assertInstanceOf(StaffTransfer::class, $transferData);
        $this->assertEquals($this->staff->id, $transferData->staff_id);
        $this->assertEquals($this->targetOffice->id, $transferData->new_office_id);
        $this->assertEquals($this->targetDepartment->id, $transferData->new_department_id);
        $this->assertEquals($this->newSupervisor->id, $transferData->new_supervisor_id);
        $this->assertEquals('Career development opportunity', $transferData->reason);
        $this->assertEquals(StaffTransferStatus::PENDING, $transferData->status);
    }

    public function test_it_validates_effective_date_not_in_past(): void
    {
        $this->expectException(InvalidTransferException::class);

        StaffTransfer::create([
            'staff_id' => $this->staff->id,
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'effective_date' => now()->subDay(), // Past date
            'reason' => 'Invalid date',
            'status' => StaffTransferStatus::PENDING,
        ]);
    }

    public function test_it_cannot_modify_final_status_transfers(): void
    {
        $transfer = StaffTransfer::factory()->for($this->staff)->create([
            'current_office_id' => $this->sourceOffice->id,
            'new_office_id' => $this->targetOffice->id,
            'status' => StaffTransferStatus::COMPLETED,
        ]);

        $this->assertFalse($transfer->canBeModified());
        $this->assertFalse($transfer->approve('Should not work'));
        $this->assertFalse($transfer->reject('Should not work'));
        $this->assertFalse($transfer->cancel('Should not work'));
    }
}