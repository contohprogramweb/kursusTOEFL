<?php

namespace App\Notifications\MultiChannel;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class StreakWarningNotification extends MultiChannelNotification
{
    protected string $category = 'streak';
    protected string $eventType = 'streak_warning';
    protected bool $isUrgent = false;
    protected ?string $rateLimitType = null;

    protected int $currentStreak;
    protected int $targetStreak;
    protected string $hoursLeft;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        int $currentStreak,
        int $targetStreak,
        string $hoursLeft
    ) {
        $this->currentStreak = $currentStreak;
        $this->targetStreak = $targetStreak;
        $this->hoursLeft = $hoursLeft;

        $this->templateVariables = [
            'nama' => '',
            'current_streak' => (string) $currentStreak,
            'target_streak' => (string) $targetStreak,
            'hours_left' => $hoursLeft,
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
            ->subject('🔥 Jangan Sampai Putus! Streak Belajar Anda')
            ->greeting('Halo ' . $userName . '!')
            ->line($this->renderTemplate('Streak belajar Anda saat ini: **{{current_streak}} hari** 🔥'))
            ->line($this->renderTemplate('Ayo capai target **{{target_streak}} hari**!'))
            ->line($this->renderTemplate('⏰ Waktu tersisa: {{hours_left}}'))
            ->action('Mulai Belajar Sekarang', route('student.dashboard'))
            ->line('Jangan biarkan streak-mu putus! Semangat! 💪');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return [
            'title' => '🔥 Streak Terancam!',
            'body' => $this->renderTemplate('Streak {{current_streak}} hari. Sisa {{hours_left}}!'),
            'data' => [
                'type' => 'streak_warning',
                'current_streak' => $this->currentStreak,
                'target_streak' => $this->targetStreak,
                'hours_left' => $this->hoursLeft,
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
            "🔥 Halo {{nama}}, streak Anda: {{current_streak}} hari. " .
            "Sisa {{hours_left}} untuk menjaga streak! Ayo belajar: " . route('student.dashboard')
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
            "🔥 *Streak Belajar Terancam!*\n\n" .
            "Halo {{nama}},\n\n" .
            "Streak saat ini: *{{current_streak}} hari* 🔥\n" .
            "Target: *{{target_streak}} hari*\n" .
            "⏰ Waktu tersisa: *{{hours_left}}*\n\n" .
            "Jangan biarkan streak-mu putus!\n" .
            "Ayo belajar sekarang:\n" .
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
            'type' => 'streak_warning',
            'current_streak' => $this->currentStreak,
            'target_streak' => $this->targetStreak,
            'hours_left' => $this->hoursLeft,
            'title' => 'Streak Terancam!',
            'message' => $this->renderTemplate('Streak {{current_streak}} hari. Sisa {{hours_left}}!'),
            'click_url' => route('student.dashboard'),
        ];
    }
}
