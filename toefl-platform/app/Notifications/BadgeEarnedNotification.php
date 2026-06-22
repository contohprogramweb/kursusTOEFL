<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class BadgeEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $badgeCode;
    protected string $badgeName;
    protected string $badgeIcon;
    protected int $points;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $badgeCode, string $badgeName, string $badgeIcon, int $points)
    {
        $this->badgeCode = $badgeCode;
        $this->badgeName = $badgeName;
        $this->badgeIcon = $badgeIcon;
        $this->points = $points;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🏆 Selamat! Anda Mendapat Lencana {$this->badgeIcon}")
            ->greeting('Selamat ' . $notifiable->full_name . '!')
            ->line("Anda telah mendapatkan lencana **{$this->badgeName}**!")
            ->line("Points: +{$this->points}")
            ->action('Lihat Profil Saya', url('/profile'))
            ->line('Terus pertahankan semangat belajarmu! 🎉');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'badge_earned',
            'badge_code' => $this->badgeCode,
            'badge_name' => $this->badgeName,
            'badge_icon' => $this->badgeIcon,
            'points' => $this->points,
            'message' => "Selamat! Anda mendapat lencana {$this->badgeIcon} {$this->badgeName}",
            'action_url' => '/profile',
            'show_confetti' => true,
            'confetti_duration' => 2000, // 2 seconds as per SRS
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'badge_earned',
            'badge_code' => $this->badgeCode,
            'badge_name' => $this->badgeName,
            'badge_icon' => $this->badgeIcon,
            'points' => $this->points,
            'message' => "Selamat! Anda mendapat lencana {$this->badgeIcon} {$this->badgeName}",
            'show_confetti' => true,
            'confetti_duration' => 2000,
        ]);
    }
}
