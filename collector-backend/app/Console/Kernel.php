<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */    protected function schedule(Schedule $schedule): void
    {
        // Reset is_read and is_paid flags every 10th of the month at midnight Philippines time
        $schedule->call(function () {
            $now = Carbon::now('Asia/Manila');
            
            if ($now->day === 10) {
                DB::table('members')
                    ->update([
                        'is_read' => 0,
                        'is_paid' => 0
                    ]);
            }
        })->dailyAt('00:01')->timezone('Asia/Manila');
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
