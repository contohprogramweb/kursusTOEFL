<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Gamification - Nightly Streak Calculation
 * Runs every day at midnight to calculate and update streaks
 */
Schedule::call(function () {
    $gamificationService = app(\App\Services\GamificationService::class);
    $gamificationService->calculateNightlyStreaks();
})->dailyAt('00:00')->name('gamification:calculate-streaks');

/**
 * Gamification - Streak Warning Notifications
 * Runs every day at 6 PM to send "Streak akan terputus dalam 6 jam" notifications
 */
Schedule::call(function () {
    $gamificationService = app(\App\Services\GamificationService::class);
    $gamificationService->sendStreakWarnings();
})->dailyAt('18:00')->name('gamification:streak-warnings');

/**
 * Badge Check for Completed Exercises
 * This could also be triggered on-demand when exercises are completed
 */
Schedule::call(function () {
    // Optional: Run periodic badge checks for users who completed activities
    // Most badge checks are done in real-time via the service
})->hourly()->name('gamification:badge-checks');

/**
 * Send study plan reminders daily at 7:00 AM
 */
Schedule::command('study-plan:send-reminders')
    ->dailyAt('07:00')
    ->name('study-plan-reminders')
    ->withoutOverlapping();

/**
 * Optional: Clean up old reminders (weekly)
 */
Schedule::command('study-plan:cleanup-reminders')
    ->weekly()
    ->name('study-plan-cleanup');
