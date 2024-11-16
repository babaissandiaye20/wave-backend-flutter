<?php

namespace App\Console;

use Illuminate\Support\Facades\Log;
use App\Models\TransactionPlanifiee;
use App\Services\TransactionService;
use App\Jobs\ExecuteScheduledTransfers;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */protected function schedule(Schedule $schedule): void
{
    $schedule->call(function () {
        // Vérifier s'il existe des transactions planifiées actives
        if (TransactionPlanifiee::where('active', true)->exists()) {
            $transactionService = app(TransactionService::class);
            dispatch(new ExecuteScheduledTransfers($transactionService));
        } else {
            Log::info('Aucune transaction planifiée active. Schedule arrêté.');
        }
    })->everyMinute();
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
