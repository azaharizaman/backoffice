<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Observers;

use AzahariZaman\BackOffice\Models\Staff;

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
    }

    /**
     * Handle the Staff "updated" event.
     */
    public function updated(Staff $staff): void
    {
        // Handle post-update logic
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
}