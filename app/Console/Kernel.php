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
           // ✅ USAR COMANDOS DIRECTOS (SIN runInBackground para closures)
        $schedule->command('cups:sync-contratados --direct')
                 ->everyTwoHours()
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/scheduler.log'));
                 
        // ✅ SINCRONIZACIÓN COMPLETA DIARIA
        $schedule->command('cups:sync-contratados --force --direct')
                 ->dailyAt('02:00')
                 ->withoutOverlapping()
                 ->appendOutputTo(storage_path('logs/scheduler.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        

        require base_path('routes/console.php');
    }

    protected $commands = [
    Commands\CleanDuplicateAgendas::class,
    Commands\ClearOfflineTables::class, 
    Commands\SincronizarCups::class,
    Commands\DiagnosticarCups::class,
];

}
