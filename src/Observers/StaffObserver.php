<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Observers;

use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Enums\StaffStatus;
use AzahariZaman\BackOffice\Exceptions\InvalidResignationException;

/**
 * Staff Observer
 * 
 * Handles Staff model events.
 */
class StaffObserver
{
    /**
     * Handle the Staff "creating" event.
     */
    public function creating(Staff $staff): void
    {
        // Ensure staff has either office or department (or both)
        if (!$staff->office_id && !$staff->department_id) {
            throw new \InvalidArgumentException('Staff must belong to at least one office or department.');
        }

        $this->validateResignationData($staff);
    }

    /**
     * Handle the Staff "created" event.
     */
    public function created(Staff $staff): void
    {
        // Log staff creation
    }

    /**
     * Handle the Staff "updating" event.
     */
    public function updating(Staff $staff): void
    {
        // Ensure staff has either office or department (or both)
        if (!$staff->office_id && !$staff->department_id) {
            throw new \InvalidArgumentException('Staff must belong to at least one office or department.');
        }

        $this->validateResignationData($staff);
        $this->handleResignationStatusChanges($staff);
    }

    /**
     * Handle the Staff "updated" event.
     */
    public function updated(Staff $staff): void
    {
        // Log resignation status changes if needed
        if ($staff->wasChanged('status') && $staff->status === StaffStatus::RESIGNED) {
            // You could add logging here if needed
        }
    }

    /**
     * Handle the Staff "deleted" event.
     */
    public function deleted(Staff $staff): void
    {
        // Handle cleanup - remove from units
        $staff->units()->detach();
    }

    /**
     * Handle the Staff "restored" event.
     */
    public function restored(Staff $staff): void
    {
        // Handle restoration logic
    }

    /**
     * Validate resignation data consistency.
     */
    private function validateResignationData(Staff $staff): void
    {
        // If resignation date is set, ensure it's not in the past for new entries
        if ($staff->resignation_date && !$staff->exists) {
            if ($staff->resignation_date < now()->toDateString()) {
                throw new InvalidResignationException(
                    'Resignation date cannot be in the past for new staff entries.'
                );
            }
        }

        // If status is RESIGNED, ensure resigned_at is set
        if ($staff->status === StaffStatus::RESIGNED && !$staff->resigned_at) {
            $staff->resigned_at = now();
        }

        // If status is RESIGNED, ensure is_active is false
        if ($staff->status === StaffStatus::RESIGNED) {
            $staff->is_active = false;
        }

        // If resignation is cancelled, clean up related fields
        if ($staff->isDirty('resignation_date') && !$staff->resignation_date) {
            $staff->resignation_reason = null;
        }
    }

    /**
     * Handle resignation status changes.
     */
    private function handleResignationStatusChanges(Staff $staff): void
    {
        // If changing TO resigned status
        if ($staff->isDirty('status') && $staff->status === StaffStatus::RESIGNED) {
            if (!$staff->resigned_at) {
                $staff->resigned_at = now();
            }
            $staff->is_active = false;
        }

        // If changing FROM resigned status to active status
        if ($staff->isDirty('status') && 
            $staff->getOriginal('status') === StaffStatus::RESIGNED->value && 
            $staff->status === StaffStatus::ACTIVE) {
            // Clear resignation data when reactivating
            $staff->resignation_date = null;
            $staff->resignation_reason = null;
            $staff->resigned_at = null;
            $staff->is_active = true;
        }
    }
}