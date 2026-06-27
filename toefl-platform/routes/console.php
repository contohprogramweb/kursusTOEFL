<?php

use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\SendStudyPlanReminders;

/*
|--------------------------------------------------------------------------
| Console Routes - Laravel Scheduler
|--------------------------------------------------------------------------
*/

// Send study plan reminders daily at 7:00 AM
Schedule::command('study-plan:send-reminders')
    ->dailyAt('07:00')
    ->name('study-plan-reminders')
    ->withoutOverlapping();

// Optional: Clean up old reminders (weekly)
Schedule::command('study-plan:cleanup-reminders')
    ->weekly()
    ->name('study-plan-cleanup');
