<?php

namespace App\Jobs;

use App\Models\Deadline;
use App\Models\User;
use App\Notifications\DeadlineAlertNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDeadlineAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    public function __construct(
        private Deadline $deadline,
        private int $daysUntilDue
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get responsible lawyer
            $lawyer = $this->deadline->lawsuit->responsibleLawyer;
            
            if (!$lawyer) {
                Log::warning("No responsible lawyer found for deadline: {$this->deadline->id}");
                return;
            }

            // Send notification to lawyer
            $lawyer->notify(new DeadlineAlertNotification($this->deadline, $this->daysUntilDue));

            // Also notify other users who have permission to view this lawsuit
            $additionalUsers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'partner']);
            })->where('id', '!=', $lawyer->id)->get();

            foreach ($additionalUsers as $user) {
                $user->notify(new DeadlineAlertNotification($this->deadline, $this->daysUntilDue));
            }

            Log::info("Deadline alert sent successfully", [
                'deadline_id' => $this->deadline->id,
                'lawsuit_number' => $this->deadline->lawsuit->number,
                'days_until_due' => $this->daysUntilDue,
                'recipients' => $additionalUsers->count() + 1
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send deadline alert", [
                'deadline_id' => $this->deadline->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Deadline alert job failed", [
            'deadline_id' => $this->deadline->id,
            'error' => $exception->getMessage()
        ]);
    }
}