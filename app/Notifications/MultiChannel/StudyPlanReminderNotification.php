<?php

namespace App\Notifications\MultiChannel;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class StudyPlanReminderNotification extends MultiChannelNotification
{
    protected string $category = 'study_plan';
    protected string $eventType = 'study_plan_reminder';
    protected bool $isUrgent = false;
    protected ?string $rateLimitType = null;

    protected string $taskTitle;
    protected string $planName;
    protected int $estimatedMinutes;
    protected string $section;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $taskTitle,
        string $planName,
        int $estimatedMinutes,
        string $section
    ) {
        $this->taskTitle = $taskTitle;
        $this->planName = $planName;
        $this->estimatedMinutes = $estimatedMinutes;
        $this->section = $section;

        $this->templateVariables = [
            'nama' => '',
            'task_title' => $taskTitle,
            'plan_name' => $planName,
            'estimated_minutes' => (string) $estimatedMinutes,
            'section' => ucfirst($section),
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return (new MailMessage)
            ->subject('📚 Reminder: Tugas Belajar Hari Ini')
            ->greeting('Halo ' . $userName . '!')
            ->line($this->renderTemplate('Jangan lupa menyelesaikan tugas belajar kamu hari ini:'))
            ->line($this->renderTemplate('**{{task_title}}**'))
            ->line($this->renderTemplate('Estimasi waktu: {{estimated_minutes}} menit'))
            ->line($this->renderTemplate('Section: {{section}}'))
            ->line($this->renderTemplate('Dari plan: {{plan_name}}'))
            ->action('Buka Study Plan', route('student.dashboard'))
            ->line('Semangat belajar! 💪');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return [
            'title' => '📚 Reminder Belajar',
            'body' => $this->renderTemplate('{{task_title}} ({{estimated_minutes}} menit)'),
            'data' => [
                'type' => 'study_plan_reminder',
                'task_title' => $this->taskTitle,
                'plan_name' => $this->planName,
                'estimated_minutes' => $this->estimatedMinutes,
                'section' => $this->section,
                'click_action' => route('student.dashboard'),
            ],
            'icon' => 'notification_icon',
        ];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return $this->renderTemplate(
            "Halo {{nama}}, reminder: {{task_title}} ({{estimated_minutes}} menit). " .
            "Section: {{section}}. Ayo mulai: " . route('student.dashboard')
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return $this->renderTemplate(
            "📚 *Reminder Tugas Belajar*\n\n" .
            "Halo {{nama}},\n\n" .
            "*{{task_title}}*\n" .
            "Estimasi: {{estimated_minutes}} menit\n" .
            "Section: {{section}}\n" .
            "Plan: {{plan_name}}\n\n" .
            "Jangan lupa selesaikan tugasmu hari ini!\n" .
            route('student.dashboard')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return [
            'type' => 'study_plan_reminder',
            'task_title' => $this->taskTitle,
            'plan_name' => $this->planName,
            'estimated_minutes' => $this->estimatedMinutes,
            'section' => $this->section,
            'title' => 'Reminder Belajar',
            'message' => $this->renderTemplate('{{task_title}} ({{estimated_minutes}} menit)'),
            'click_url' => route('student.dashboard'),
        ];
    }
}
