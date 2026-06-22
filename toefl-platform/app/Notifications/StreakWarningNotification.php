<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StreakWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected int $currentStreak;
    protected string $streakType; // 'will_break' or 'broken'

    /**
     * Create a new notification instance.
     */
    public function __construct(int $currentStreak, string $streakType = 'will_break')
    {
        $this->currentStreak = $currentStreak;
        $this->streakType = $streakType;
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
        if ($this->streakType === 'will_break') {
            return (new MailMessage)
                ->subject('⚠️ Streak Anda Akan Terputus!')
                ->greeting('Halo ' . $notifiable->full_name . '!')
                ->line('Streak belajar Anda saat ini adalah **' . $this->currentStreak . ' hari**.')
                ->line('Jangan sampai terputus! Luangkan waktu minimal 15 menit untuk belajar hari ini.')
                ->action('Mulai Belajar Sekarang', url('/exercise'))
                ->line('Tetap semangat! 🔥');
        } else {
            return (new MailMessage)
                ->subject('😔 Streak Anda Terputus')
                ->greeting('Halo ' . $notifiable->full_name . '!')
                ->line('Streak belajar Anda yang mencapai **' . $this->currentStreak . ' hari** telah terputus.')
                ->line('Jangan menyerah! Mulai streak baru hari ini dan bangun momentum lagi.')
                ->action('Mulai Streak Baru', url('/exercise'))
                ->line('Kamu pasti bisa! 💪');
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'streak_' . $this->streakType,
            'current_streak' => $this->currentStreak,
            'message' => $this->streakType === 'will_break' 
                ? "Streak {$this->currentStreak} hari Anda akan terputus dalam 6 jam!"
                : "Streak {$this->currentStreak} hari Anda telah terputus.",
            'action_url' => '/exercise',
            'icon' => '🔥',
        ];
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'streak_' . $this->streakType,
            'current_streak' => $this->currentStreak,
            'message' => $this->streakType === 'will_break'
                ? "Streak {$this->currentStreak} hari Anda akan terputus dalam 6 jam!"
                : "Streak {$this->currentStreak} hari Anda telah terputus.",
            'icon' => '🔥',
        ]);
    }
}
