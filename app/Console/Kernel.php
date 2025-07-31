<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Sync court movements daily at 6 AM
        $schedule->command('juridico:sync-court-movements')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->runInBackground();

        // Check deadlines every hour
        $schedule->command('juridico:check-deadlines')
                 ->hourly()
                 ->withoutOverlapping();

        // Send invoice reminders daily at 9 AM
        $schedule->command('juridico:send-invoice-reminders')
                 ->dailyAt('09:00')
                 ->withoutOverlapping();

        // Auto-reconcile bank transactions daily at 7 AM
        $schedule->command('juridico:auto-reconcile')
                 ->dailyAt('07:00')
                 ->withoutOverlapping();

        // Generate recurring invoices on the 1st of each month
        $schedule->command('juridico:generate-recurring-invoices')
                 ->monthlyOn(1, '08:00')
                 ->withoutOverlapping();

        // Update overdue invoices daily at 8 AM
        $schedule->command('juridico:update-overdue-invoices')
                 ->dailyAt('08:00')
                 ->withoutOverlapping();

        // Clean up old notifications weekly
        $schedule->command('juridico:cleanup-notifications')
                 ->weekly()
                 ->sundays()
                 ->at('02:00');

        // Backup database daily at 2 AM
        $schedule->command('backup:run --only-db')
                 ->dailyAt('02:00')
                 ->withoutOverlapping();

        // Clean old backups weekly
        $schedule->command('backup:clean')
                 ->weekly()
                 ->sundays()
                 ->at('03:00');

        // Update holiday calendar yearly
        $schedule->command('juridico:update-holidays')
                 ->yearly()
                 ->at('01:00');

        // Generate financial reports monthly
        $schedule->command('juridico:generate-monthly-reports')
                 ->monthlyOn(1, '10:00')
                 ->withoutOverlapping();

        // Sync OAB data weekly
        $schedule->command('juridico:sync-oab-data')
                 ->weekly()
                 ->mondays()
                 ->at('05:00');

        // Auto-stop inactive timers every 15 minutes
        $schedule->command('juridico:auto-stop-timers')
                 ->everyFifteenMinutes()
                 ->withoutOverlapping();

        // Send deadline alerts every morning at 8 AM
        $schedule->command('juridico:send-deadline-alerts')
                 ->dailyAt('08:00')
                 ->withoutOverlapping();

        // Process document signatures every 30 minutes
        $schedule->command('juridico:process-signatures')
                 ->everyThirtyMinutes()
                 ->withoutOverlapping();

        // Update lawsuit statuses daily
        $schedule->command('juridico:update-lawsuit-statuses')
                 ->dailyAt('07:30')
                 ->withoutOverlapping();

        // Clean temporary files daily
        $schedule->command('juridico:cleanup-temp-files')
                 ->dailyAt('01:00');

        // Index documents for search daily
        $schedule->command('juridico:index-documents')
                 ->dailyAt('04:00')
                 ->withoutOverlapping();

        // Send system health report weekly
        $schedule->command('juridico:system-health-report')
                 ->weekly()
                 ->mondays()
                 ->at('07:00');

        // Prune old activity logs monthly
        $schedule->command('activitylog:clean')
                 ->monthly()
                 ->at('01:30');

        // Queue maintenance
        $schedule->command('queue:prune-batches --hours=48')
                 ->daily()
                 ->at('01:00');

        $schedule->command('queue:prune-failed --hours=48')
                 ->daily()
                 ->at('01:15');

        // Horizon snapshots (if using Horizon)
        $schedule->command('horizon:snapshot')
                 ->everyFiveMinutes();

        // Telescope pruning (if using Telescope)
        $schedule->command('telescope:prune --hours=48')
                 ->daily()
                 ->at('02:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}