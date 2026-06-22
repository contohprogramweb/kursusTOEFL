<?php

namespace App\Notifications\MultiChannel;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class ForumReplyNotification extends MultiChannelNotification
{
    protected string $category = 'forum';
    protected string $eventType = 'forum_reply';
    protected bool $isUrgent = false;
    protected ?string $rateLimitType = null; // No rate limit for forum replies

    protected int $threadId;
    protected string $threadTitle;
    protected string $replierName;
    protected string $replyPreview;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        int $threadId,
        string $threadTitle,
        string $replierName,
        string $replyPreview
    ) {
        $this->threadId = $threadId;
        $this->threadTitle = $threadTitle;
        $this->replierName = $replierName;
        $this->replyPreview = substr(strip_tags($replyPreview), 0, 100) . '...';

        $this->templateVariables = [
            'nama' => '',
            'thread_title' => $threadTitle,
            'replier_name' => $replierName,
            'reply_preview' => $this->replyPreview,
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
            ->subject('💬 Balasan Baru di Thread: ' . $this->threadTitle)
            ->greeting('Halo ' . $userName . '!')
            ->line($this->renderTemplate('{{replier_name}} telah membalas thread Anda:'))
            ->line($this->renderTemplate('**{{thread_title}}**'))
            ->line($this->renderTemplate('"{{reply_preview}}"'))
            ->action('Lihat Diskusi', route('forum.show', $this->threadId))
            ->line('Jangan lupa untuk berpartisipasi dalam diskusi!');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        return [
            'title' => '💬 Balasan Baru',
            'body' => $this->renderTemplate('{{replier_name}} membalas: {{thread_title}}'),
            'data' => [
                'type' => 'forum_reply',
                'thread_id' => $this->threadId,
                'thread_title' => $this->threadTitle,
                'replier_name' => $this->replierName,
                'click_action' => route('forum.show', $this->threadId),
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
            "Halo {{nama}}, {{replier_name}} membalas thread Anda: {{thread_title}}. " .
            "Cek: " . route('forum.show', $this->threadId)
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
            "💬 *Balasan Baru di Forum*\n\n" .
            "Halo {{nama}},\n" .
            "{{replier_name}} telah membalas thread Anda:\n\n" .
            "*{{thread_title}}*\n" .
            "_\"{{reply_preview}}\"_\n\n" .
            "Klik link berikut untuk melihat diskusi:\n" .
            route('forum.show', $this->threadId)
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
            'type' => 'forum_reply',
            'thread_id' => $this->threadId,
            'thread_title' => $this->threadTitle,
            'replier_name' => $this->replierName,
            'reply_preview' => $this->replyPreview,
            'title' => 'Balasan Baru di Thread',
            'message' => $this->renderTemplate('{{replier_name}} membalas: {{thread_title}}'),
            'click_url' => route('forum.show', $this->threadId),
        ];
    }
}
