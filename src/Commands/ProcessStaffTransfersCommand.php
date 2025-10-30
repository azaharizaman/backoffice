<?php

declare(strict_types=1);

namespace AzahariZaman\BackOffice\Commands;

use Illuminate\Console\Command;
use AzahariZaman\BackOffice\Models\StaffTransfer;
use AzahariZaman\BackOffice\Models\Staff;
use AzahariZaman\BackOffice\Enums\StaffTransferStatus;
use Carbon\Carbon;

/**
 * Process Staff Transfers Command
 * 
 * Processes approved staff transfers that have reached their effective date.
 * Can be run manually or scheduled to run automatically.
 * 
 * @package AzahariZaman\BackOffice\Commands
 */
class ProcessStaffTransfersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backoffice:process-transfers
                           {--dry-run : Show what would be processed without making changes}
                           {--date= : Process transfers for specific date (YYYY-MM-DD)}
                           {--batch-size=50 : Number of transfers to process in each batch}
                           {--staff-id= : Process transfers for specific staff ID only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process approved staff transfers that have reached their effective date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting staff transfer processing...');
        
        $dryRun = $this->option('dry-run');
        $date = $this->option('date');
        $batchSize = (int) $this->option('batch-size');
        $staffId = $this->option('staff-id');
        
        // Validate inputs
        if ($date && !$this->isValidDate($date)) {
            $this->error('Invalid date format. Please use YYYY-MM-DD format.');
            return self::FAILURE;
        }
        
        if ($batchSize <= 0) {
            $this->error('Batch size must be a positive integer.');
            return self::FAILURE;
        }
        
        try {
            $effectiveDate = $date ? Carbon::parse($date) : now();
            
            if ($dryRun) {
                $this->warn('DRY RUN MODE - No changes will be made');
            }
            
            $this->info("Processing transfers effective on or before: {$effectiveDate->toDateString()}");
            
            $processedCount = $this->processTransfers($effectiveDate, $batchSize, $staffId, $dryRun);
            
            if ($dryRun) {
                $this->info("DRY RUN: Would have processed {$processedCount} transfers");
            } else {
                $this->info("Successfully processed {$processedCount} transfers");
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to process transfers: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            
            return self::FAILURE;
        }
    }
    
    /**
     * Process the transfers.
     */
    protected function processTransfers(Carbon $effectiveDate, int $batchSize, ?string $staffId, bool $dryRun): int
    {
        $query = StaffTransfer::query()
            ->with(['staff', 'fromOffice', 'toOffice', 'fromDepartment', 'toDepartment', 'fromSupervisor', 'toSupervisor'])
            ->approved()
            ->where('effective_date', '<=', $effectiveDate->toDateString());
            
        if ($staffId) {
            $query->where('staff_id', $staffId);
        }
        
        $totalTransfers = $query->count();
        
        if ($totalTransfers === 0) {
            $this->info('No transfers found to process.');
            return 0;
        }
        
        $this->info("Found {$totalTransfers} transfers to process");
        
        $processed = 0;
        $failed = 0;
        
        // Create progress bar
        $progressBar = $this->output->createProgressBar($totalTransfers);
        $progressBar->start();
        
        // Process in batches
        $query->chunk($batchSize, function ($transfers) use ($dryRun, &$processed, &$failed, $progressBar) {
            foreach ($transfers as $transfer) {
                try {
                    if ($this->processTransfer($transfer, $dryRun)) {
                        $processed++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->error("Failed to process transfer {$transfer->id}: " . $e->getMessage());
                    $failed++;
                }
                
                $progressBar->advance();
            }
        });
        
        $progressBar->finish();
        $this->newLine();
        
        if ($failed > 0) {
            $this->warn("Failed to process {$failed} transfers");
        }
        
        // Show summary table
        $this->showProcessingSummary($processed, $failed, $dryRun);
        
        return $processed;
    }
    
    /**
     * Process a single transfer.
     */
    protected function processTransfer(StaffTransfer $transfer, bool $dryRun): bool
    {
        if (!$transfer->isDueForProcessing()) {
            $this->warn("Transfer {$transfer->id} is not due for processing yet");
            return false;
        }
        
        if ($dryRun) {
            $this->showTransferDetails($transfer, 'WOULD PROCESS');
            return true;
        }
        
        // Find a system user to process the transfer
        $systemProcessor = $this->getSystemProcessor();
        
        if (!$systemProcessor) {
            $this->error("No system processor found for transfer {$transfer->id}");
            return false;
        }
        
        // Complete the transfer
        $transfer->complete($systemProcessor);
        
        $this->showTransferDetails($transfer, 'PROCESSED');
        
        return true;
    }
    
    /**
     * Get a system user to process transfers.
     */
    protected function getSystemProcessor(): ?Staff
    {
        // Try to find an HR staff member or system admin
        return Staff::query()
            ->active()
            ->where(function ($query) {
                $query->whereHas('department', function ($q) {
                    $q->where('code', 'HR');
                })->orWhere('position', 'like', '%HR%')
                  ->orWhere('position', 'like', '%admin%')
                  ->orWhere('position', 'like', '%system%');
            })
            ->first();
    }
    
    /**
     * Show transfer processing details.
     */
    protected function showTransferDetails(StaffTransfer $transfer, string $action): void
    {
        if ($this->output->isVerbose()) {
            $this->newLine();
            $this->info("{$action}: Transfer #{$transfer->id}");
            $this->line("  Staff: {$transfer->staff->full_name} (ID: {$transfer->staff->employee_id})");
            $this->line("  From: {$transfer->fromOffice->name}");
            $this->line("  To: {$transfer->toOffice->name}");
            $this->line("  Effective: {$transfer->effective_date->toDateString()}");
            
            if ($transfer->fromDepartment || $transfer->toDepartment) {
                $fromDept = $transfer->fromDepartment?->name ?? 'None';
                $toDept = $transfer->toDepartment?->name ?? 'None';
                $this->line("  Department: {$fromDept} → {$toDept}");
            }
            
            if ($transfer->fromSupervisor || $transfer->toSupervisor) {
                $fromSup = $transfer->fromSupervisor?->full_name ?? 'None';
                $toSup = $transfer->toSupervisor?->full_name ?? 'None';
                $this->line("  Supervisor: {$fromSup} → {$toSup}");
            }
        }
    }
    
    /**
     * Show processing summary.
     */
    protected function showProcessingSummary(int $processed, int $failed, bool $dryRun): void
    {
        $headers = ['Metric', 'Count'];
        $rows = [
            ['Total Processed', $processed],
            ['Failed', $failed],
            ['Success Rate', $processed + $failed > 0 ? round(($processed / ($processed + $failed)) * 100, 2) . '%' : '0%'],
        ];
        
        if ($dryRun) {
            $rows[0][0] = 'Would Process';
            $rows[1][0] = 'Would Fail';
        }
        
        $this->newLine();
        $this->table($headers, $rows);
        
        // Show recent transfers if verbose
        if ($this->output->isVerbose() && $processed > 0) {
            $this->showRecentTransfers();
        }
    }
    
    /**
     * Show recently completed transfers.
     */
    protected function showRecentTransfers(): void
    {
        $recentTransfers = StaffTransfer::query()
            ->with(['staff', 'fromOffice', 'toOffice'])
            ->completed()
            ->where('completed_at', '>=', now()->subHour())
            ->orderBy('completed_at', 'desc')
            ->limit(10)
            ->get();
            
        if ($recentTransfers->isNotEmpty()) {
            $this->newLine();
            $this->info('Recent Transfers (last hour):');
            
            $headers = ['ID', 'Staff', 'From Office', 'To Office', 'Completed At'];
            $rows = $recentTransfers->map(function ($transfer) {
                return [
                    $transfer->id,
                    $transfer->staff->full_name,
                    $transfer->fromOffice->name,
                    $transfer->toOffice->name,
                    $transfer->completed_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();
            
            $this->table($headers, $rows);
        }
    }
    
    /**
     * Validate date format.
     */
    protected function isValidDate(string $date): bool
    {
        try {
            Carbon::createFromFormat('Y-m-d', $date);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}