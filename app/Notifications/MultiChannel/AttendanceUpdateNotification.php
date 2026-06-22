<?php

namespace App\Notifications\MultiChannel;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

class AttendanceUpdateNotification extends MultiChannelNotification
{
    protected string $category = 'attendance';
    protected string $eventType = 'attendance_updated';
    protected bool $isUrgent = false;
    protected ?string $rateLimitType = null;

    protected string $status; // present, late, absent, excused
    protected string $date;
    protected ?string $sessionName;
    protected ?string $notes;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $status,
        string $date,
        ?string $sessionName = null,
        ?string $notes = null
    ) {
        $this->status = $status;
        $this->date = $date;
        $this->sessionName = $sessionName;
        $this->notes = $notes;

        $this->templateVariables = [
            'nama' => '',
            'status' => ucfirst($status),
            'date' => $date,
            'session_name' => $sessionName ?? '-',
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        $emoji = match ($this->status) {
            'present' => '✅',
            'late' => '⏰',
            'absent' => '❌',
            'excused' => '📝',
            default => '📢',
        };

        return (new MailMessage)
            ->subject($emoji . ' Update Kehadiran: ' . $this->date)
            ->greeting('Halo ' . $userName . '!')
            ->line($this->renderTemplate('Status kehadiran Anda pada **{{date}}**:'))
            ->line($this->renderTemplate($emoji . ' *{{status}}**'))
            ->when($this->sessionName, function ($mail) {
                return $mail->line($this->renderTemplate('Sesi: {{session_name}}'));
            })
            ->when($this->notes, function ($mail) use ($userName) {
                return $mail->line($this->notes);
            })
            ->action('Lihat Riwayat Kehadiran', route('student.attendance.index'))
            ->line('Terima kasih atas perhatian Anda!');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        $userName = $notifiable instanceof User ? $notifiable->name : 'Siswa';
        $this->templateVariables['nama'] = $userName;

        $title = match ($this->status) {
            'present' => 'Kehadiran Dicatat',
            'late' => 'Terlambat Dicatat',
            'absent' => 'Tidak Hadir',
            'excused' => 'Izin Diterima',
            default => 'Update Kehadiran',
        };

        return [
            'title' => $title,
            'body' => $this->renderTemplate('Tanggal: {{date}} - Status: {{status}}'),
            'data' => [
                'type' => 'attendance_updated',
                'status' => $this->status,
                'date' => $this->date,
                'session_name' => $this->sessionName,
                'click_action' => route('student.attendance.index'),
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
            "Halo {{nama}}, kehadiran Anda tgl {{date}}: {{status}}. " .
            ($this->sessionName ? "Sesi: {{session_name}}. " : "") .
            "Info: " . route('student.attendance.index')
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
            'present' => '✅',
            'late' => '⏰',
            'absent' => '❌',
            'excused' => '📝',
            default => '📢',
        };

        return $this->renderTemplate(
            "{$emoji} *Update Kehadiran*\n\n" .
            "Halo {{nama}},\n\n" .
            "Tanggal: *{{date}}*\n" .
            "Status: *{{status}}*\n" .
            ($this->sessionName ? "Sesi: {{session_name}}\n" : "") .
            ($this->notes ? "Catatan: {$this->notes}\n" : "") .
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
            'type' => 'attendance_updated',
            'status' => $this->status,
            'date' => $this->date,
            'session_name' => $this->sessionName,
            'notes' => $this->notes,
            'title' => 'Update Kehadiran',
            'message' => $this->renderTemplate('Status: {{status}} pada {{date}}'),
            'click_url' => route('student.attendance.index'),
        ];
    }
}
