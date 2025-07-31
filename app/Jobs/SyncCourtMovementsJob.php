<?php

namespace App\Jobs;

use App\Models\Lawsuit;
use App\Services\CourtIntegrationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncCourtMovementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;
    public $backoff = [60, 120, 300]; // Retry after 1, 2, and 5 minutes

    public function __construct(
        private Lawsuit $lawsuit
    ) {
        $this->onQueue('court-sync');
    }

    /**
     * Execute the job.
     */
    public function handle(CourtIntegrationService $courtService): void
    {
        try {
            Log::info("Starting court movements sync for lawsuit: {$this->lawsuit->number}");

            $result = $courtService->syncMovements($this->lawsuit);

            Log::info("Court movements sync completed for lawsuit: {$this->lawsuit->number}", [
                'new_movements' => $result['new_movements'],
                'total_movements' => $result['total_movements']
            ]);

            // Dispatch notification if new movements found
            if ($result['new_movements'] > 0) {
                SendNewMovementNotificationJob::dispatch($this->lawsuit, $result['new_movements']);
            }

        } catch (\Exception $e) {
            Log::error("Failed to sync court movements for lawsuit: {$this->lawsuit->number}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Court movements sync job failed for lawsuit: {$this->lawsuit->number}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Notify administrators about the failure
        SendSystemAlertJob::dispatch(
            'Court Sync Failed',
            "Failed to sync movements for lawsuit {$this->lawsuit->number}: {$exception->getMessage()}"
        );
    }
}