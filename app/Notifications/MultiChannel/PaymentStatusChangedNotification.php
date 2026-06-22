<?php

namespace App\Notifications\MultiChannel;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentStatusChangedNotification extends MultiChannelNotification
{
    protected string $category = 'payment';
    protected string $eventType = 'payment_status_changed';
    protected bool $isUrgent = true; // Payment notifications are urgent
    protected ?string $rateLimitType = 'payment_daily';

    protected string $status;
    protected string $packageName;
    protected string $price;
    protected ?string $expiryDate;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $status,
        string $packageName,
        string $price,
        ?string $expiryDate = null
    ) {
        $this->status = $status;
        $this->packageName = $packageName;
        $this->price = $price;
        $this->expiryDate = $expiryDate;

        $this->templateVariables = [
            'nama' => '', // Will be set when sending
            'paket' => $packageName,
            'harga' => $price,
            'deadline' => $expiryDate ?? '-',
            'status' => $status,
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        $subject = match ($this->status) {
            'success' => '✅ Pembayaran Berhasil',
            'pending' => '⏳ Pembayaran Menunggu Konfirmasi',
            'failed' => '❌ Pembayaran Gagal',
            'refunded' => '💰 Pembayaran Dikembalikan',
            default => '📢 Status Pembayaran Diperbarui',
        };

        return (new MailMessage)
            ->subject($this->renderTemplate($subject))
            ->greeting('Halo ' . $userName . '!')
            ->line($this->renderTemplate('Status pembayaran Anda telah diperbarui: **{{status}}**'))
            ->line($this->renderTemplate('Paket: {{paket}}'))
            ->line($this->renderTemplate('Harga: {{harga}}'))
            ->when($this->expiryDate, function ($mail) {
                return $mail->line($this->renderTemplate('Berlaku hingga: {{deadline}}'));
            })
            ->action('Lihat Detail', route('student.payments.index'))
            ->line('Terima kasih telah belajar bersama kami!');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        $title = match ($this->status) {
            'success' => 'Pembayaran Berhasil!',
            'pending' => 'Pembayaran Menunggu',
            'failed' => 'Pembayaran Gagal',
            default => 'Status Pembayaran Diperbarui',
        };

        return [
            'title' => $title,
            'body' => $this->renderTemplate('Paket {{paket}} - {{harga}}'),
            'data' => [
                'type' => 'payment_status_changed',
                'status' => $this->status,
                'package_name' => $this->packageName,
                'price' => $this->price,
                'click_action' => route('student.payments.index'),
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
            "Halo {{nama}}, status pembayaran Anda: {{status}}. Paket: {{paket}} ({{harga}}). " .
            "Info: " . route('student.payments.index')
        );
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable): string
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        $emoji = match ($this->status) {
            'success' => '✅',
            'pending' => '⏳',
            'failed' => '❌',
            'refunded' => '💰',
            default => '📢',
        };

        return $this->renderTemplate(
            "{$emoji} *Status Pembayaran Diperbarui*\n\n" .
            "Halo {{nama}},\n" .
            "Status: *{{status}}*\n" .
            "Paket: {{paket}}\n" .
            "Harga: {{harga}}\n" .
            ($this->expiryDate ? "Berlaku hingga: {{deadline}}\n" : "") .
            "\nSilakan cek aplikasi untuk detail lebih lanjut."
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
            'type' => 'payment_status_changed',
            'status' => $this->status,
            'package_name' => $this->packageName,
            'price' => $this->price,
            'expiry_date' => $this->expiryDate,
            'title' => $this->renderTemplate('Pembayaran {{status}}'),
            'message' => $this->renderTemplate('Paket {{paket}} sebesar {{harga}}'),
            'click_url' => route('student.payments.index'),
        ];
    }
}
