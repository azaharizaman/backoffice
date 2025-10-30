<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Database\Factories;

use AzahariZaman\BackOffice\Enums\StaffTransferStatus;
use AzahariZaman\BackOffice\Models\Company;
use AzahariZaman\BackOffice\Models\Department;
use AzahariZaman\BackOffice\Models\Office;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Models\StaffTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StaffTransfer>
 */
class StaffTransferFactory extends Factory
{
    protected $model = StaffTransfer::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $sourceOffice = Office::factory()->for($company)->create();
        $targetOffice = Office::factory()->for($company)->create();
        $staff = Staff::factory()->for($company)->for($sourceOffice)->create();

        return [
            'staff_id' => $staff->id,
            'current_office_id' => $sourceOffice->id,
            'new_office_id' => $targetOffice->id,
            'current_department_id' => null,
            'new_department_id' => null,
            'current_supervisor_id' => null,
            'new_supervisor_id' => null,
            'current_position' => null,
            'new_position' => null,
            'effective_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'reason' => $this->faker->sentence(),
            'status' => StaffTransferStatus::PENDING,
            'requested_by' => $staff->id,
            'requested_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'approved_by_comment' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'rejection_reason' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'completed_at' => null,
            'notes' => null,
        ];
    }

    /**
     * Configure the factory for immediate transfers.
     */
    public function immediate(): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => now(),
        ]);
    }

    /**
     * Configure the factory for scheduled transfers.
     */
    public function scheduled(?string $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date ? now()->parse($date) : now()->addWeek(),
        ]);
    }

    /**
     * Configure the factory for pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::PENDING,
        ]);
    }

    /**
     * Configure the factory for approved status.
     */
    public function approved(?string $approvedBy = null): static
    {
        $staff = Staff::first() ?? Staff::factory()->create();

        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::APPROVED,
            'approved_by' => $staff->id,
            'approved_at' => now(),
            'approved_by_comment' => $approvedBy ?? 'Transfer approved',
        ]);
    }

    /**
     * Configure the factory for rejected status.
     */
    public function rejected(?string $reason = null): static
    {
        $staff = Staff::first() ?? Staff::factory()->create();

        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::REJECTED,
            'rejected_by' => $staff->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason ?? 'Transfer rejected',
        ]);
    }

    /**
     * Configure the factory for cancelled status.
     */
    public function cancelled(?string $reason = null): static
    {
        $staff = Staff::first() ?? Staff::factory()->create();

        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::CANCELLED,
            'cancelled_by' => $staff->id,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason ?? 'Transfer cancelled',
        ]);
    }

    /**
     * Configure the factory for completed status.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StaffTransferStatus::COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Configure the factory with department change.
     */
    public function withDepartmentChange(): static
    {
        return $this->state(function (array $attributes) {
            $company = Company::find($attributes['staff_id']) 
                ? Staff::find($attributes['staff_id'])->company 
                : Company::factory()->create();

            $sourceDepartment = Department::factory()->for($company)->create();
            $targetDepartment = Department::factory()->for($company)->create();

            return [
                'current_department_id' => $sourceDepartment->id,
                'new_department_id' => $targetDepartment->id,
            ];
        });
    }

    /**
     * Configure the factory with supervisor change.
     */
    public function withSupervisorChange(): static
    {
        return $this->state(function (array $attributes) {
            $company = Company::find($attributes['staff_id']) 
                ? Staff::find($attributes['staff_id'])->company 
                : Company::factory()->create();

            $currentSupervisor = Staff::factory()->for($company)->create();
            $newSupervisor = Staff::factory()->for($company)->create();

            return [
                'current_supervisor_id' => $currentSupervisor->id,
                'new_supervisor_id' => $newSupervisor->id,
            ];
        });
    }

    /**
     * Configure the factory with position change.
     */
    public function withPositionChange(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_position' => $this->faker->jobTitle(),
            'new_position' => $this->faker->jobTitle(),
        ]);
    }

    /**
     * Configure the factory for a complete transfer with all changes.
     */
    public function complete(): static
    {
        return $this->withDepartmentChange()
                    ->withSupervisorChange()
                    ->withPositionChange();
    }
}