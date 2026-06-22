<?php

namespace App\Console\Commands;

use App\Models\StudyPlan;
use App\Models\StudyPlanTask;
use App\Models\StudyPlanReminder;
use App\Notifications\StudyPlanReminderNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

/**
 * SendStudyPlanReminders Command
 * 
 * Mengirim notifikasi reminder harian untuk tugas study plan
 * Dijalankan setiap hari jam 07:00 pagi via Laravel Scheduler
 */
class SendStudyPlanReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'study-plan:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily study plan reminders to users';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting study plan reminder job...');

        $today = now()->toDateString();
        $sentCount = 0;
        $skippedCount = 0;

        // Get all active study plans
        $activePlans = StudyPlan::where('status', 'active')
            ->where('end_date', '>=', today())
            ->get();

        foreach ($activePlans as $plan) {
            // Get today's incomplete tasks
            $todayTasks = $plan->tasks()
                ->whereJsonContains('metadata->scheduled_date', $today)
                ->where('is_completed', false)
                ->get();

            foreach ($todayTasks as $task) {
                // Check if reminder already sent today
                $existingReminder = StudyPlanReminder::where('user_id', $plan->user_id)
                    ->where('task_id', $task->id)
                    ->where('scheduled_date', $today)
                    ->first();

                if ($existingReminder && $existingReminder->is_sent) {
                    $skippedCount++;
                    continue;
                }

                // Send notification
                try {
                    $plan->user->notify(new StudyPlanReminderNotification($task, $plan->name));

                    // Log reminder
                    StudyPlanReminder::updateOrCreate(
                        [
                            'user_id' => $plan->user_id,
                            'task_id' => $task->id,
                            'scheduled_date' => $today,
                        ],
                        [
                            'is_sent' => true,
                            'sent_at' => now(),
                        ]
                    );

                    $sentCount++;
                    $this->line("✓ Reminder sent to user {$plan->user_id} for task: {$task->title}");
                } catch (\Exception $e) {
                    $this->error("Failed to send reminder: {$e->getMessage()}");
                }
            }
        }

        $this->info("Reminder job completed!");
        $this->info("Sent: {$sentCount}, Skipped: {$skippedCount}");

        return Command::SUCCESS;
    }
}
