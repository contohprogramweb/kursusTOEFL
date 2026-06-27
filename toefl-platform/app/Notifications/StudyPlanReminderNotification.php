<?php

namespace App\Notifications;

use App\Models\StudyPlanTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StudyPlanReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected StudyPlanTask $task;
    protected string $planName;

    /**
     * Create a new notification instance.
     */
    public function __construct(StudyPlanTask $task, string $planName = '')
    {
        $this->task = $task;
        $this->planName = $planName;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📚 Reminder: Tugas Belajar Hari Ini')
            ->greeting('Halo ' . ($notifiable->name ?? 'Siswa') . '!')
            ->line("Jangan lupa menyelesaikan tugas belajar kamu hari ini:")
            ->line("**{$this->task->title}**")
            ->line("Estimasi waktu: {$this->task->estimated_minutes} menit")
            ->line("Section: " . ucfirst($this->task->section))
            ->line($this->planName ? "Dari plan: {$this->planName}" : '')
            ->action('Buka Study Plan', route('student.dashboard'))
            ->line('Semangat belajar! 💪');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'task_type' => $this->task->type,
            'section' => $this->task->section,
            'estimated_minutes' => $this->task->estimated_minutes,
            'plan_name' => $this->planName,
            'scheduled_date' => $this->task->getMetadataValue('scheduled_date'),
        ];
    }
}
