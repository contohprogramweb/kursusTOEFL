<?php

namespace App\Notifications\MultiChannel;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class AssignmentCreatedNotification extends MultiChannelNotification
{
    protected string $category = 'assignment';
    protected string $eventType = 'assignment_created';
    protected bool $isUrgent = false;
    protected ?string $rateLimitType = null;

    protected int $assignmentId;
    protected string $assignmentTitle;
    protected string $courseName;
    protected string $deadline;
    protected ?string $description;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        int $assignmentId,
        string $assignmentTitle,
        string $courseName,
        string $deadline,
        ?string $description = null
    ) {
        $this->assignmentId = $assignmentId;
        $this->assignmentTitle = $assignmentTitle;
        $this->courseName = $courseName;
        $this->deadline = $deadline;
        $this->description = $description;

        $this->templateVariables = [
            'nama' => '',
            'assignment_title' => $assignmentTitle,
            'course_name' => $courseName,
            'deadline' => $deadline,
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
            ->subject('📝 Tugas Baru: ' . $this->assignmentTitle)
            ->greeting('Halo ' . $userName . '!')
            ->line($this->renderTemplate('Tugas baru telah ditambahkan di course **{{course_name}}**:'))
            ->line($this->renderTemplate('**{{assignment_title}}**'))
            ->when($this->description, function ($mail) {
                return $mail->line($this->description);
            })
            ->line($this->renderTemplate('⏰ Deadline: {{deadline}}'))
            ->action('Kerjakan Tugas', route('assignments.show', $this->assignmentId))
            ->line('Semangat mengerjakan! 💪');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return [
            'title' => '📝 Tugas Baru',
            'body' => $this->renderTemplate('{{assignment_title}} - {{course_name}}'),
            'data' => [
                'type' => 'assignment_created',
                'assignment_id' => $this->assignmentId,
                'assignment_title' => $this->assignmentTitle,
                'course_name' => $this->courseName,
                'deadline' => $this->deadline,
                'click_action' => route('assignments.show', $this->assignmentId),
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
            "Halo {{nama}}, tugas baru: {{assignment_title}} ({{course_name}}). " .
            "Deadline: {{deadline}}. Info: " . route('assignments.show', $this->assignmentId)
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
            "📝 *Tugas Baru*\n\n" .
            "Halo {{nama}},\n\n" .
            "Course: *{{course_name}}*\n" .
            "Tugas: *{{assignment_title}}*\n" .
            ($this->description ? "Deskripsi: {{description}}\n" : "") .
            "⏰ Deadline: *{{deadline}}*\n\n" .
            "Segera kerjakan tugasnya!\n" .
            route('assignments.show', $this->assignmentId)
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
            'type' => 'assignment_created',
            'assignment_id' => $this->assignmentId,
            'assignment_title' => $this->assignmentTitle,
            'course_name' => $this->courseName,
            'deadline' => $this->deadline,
            'description' => $this->description,
            'title' => 'Tugas Baru',
            'message' => $this->renderTemplate('{{assignment_title}} di {{course_name}}'),
            'click_url' => route('assignments.show', $this->assignmentId),
        ];
    }
}
