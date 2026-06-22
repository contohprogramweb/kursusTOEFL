<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Get phone number from notifiable
        $phoneNumber = $notifiable->phone ?? null;

        if (!$phoneNumber) {
            Log::warning("No phone number found for user {$notifiable->id}");
            return;
        }

        $message = $notification->toSms($notifiable);

        if (!$message) {
            Log::warning("SMS message is null for notification " . get_class($notification));
            return;
        }

        // Use Twilio API (or Infobip) via HTTP
        $config = config('services.twilio');

        if (!$config || !$config['account_sid'] || !$config['auth_token']) {
            Log::error('Twilio credentials not configured');
            return;
        }

        try {
            $response = Http::withBasicAuth(
                $config['account_sid'],
                $config['auth_token']
            )->post(
                "https://api.twilio.com/2010-04-01/Accounts/{$config['account_sid']}/Messages.json",
                [
                    'From' => $config['from_number'] ?? '+1234567890',
                    'To' => $phoneNumber,
                    'Body' => $message,
                ]
            );

            if ($response->successful()) {
                Log::info("SMS sent successfully to {$phoneNumber}");
                $this->updateDeliveryStatus($notifiable, $notification, 'sent');
            } else {
                Log::error("SMS failed: " . $response->body());
                $this->updateDeliveryStatus($notifiable, $notification, 'failed', $response->body());
            }
        } catch (\Exception $e) {
            Log::error("SMS error: " . $e->getMessage());
            $this->updateDeliveryStatus($notifiable, $notification, 'failed', $e->getMessage());
        }
    }

    /**
     * Update delivery status in database.
     */
    protected function updateDeliveryStatus(
        object $notifiable,
        Notification $notification,
        string $status,
        ?string $errorMessage = null
    ): void {
        if (method_exists($notifiable, 'notifications')) {
            $notifiable->notifications()
                ->where('type', get_class($notification))
                ->latest('created_at')
                ->first()?->update([
                    'delivery_status' => $status,
                    'sent_at' => $status === 'sent' ? now() : null,
                    'error_message' => $errorMessage,
                ]);
        }
    }
}
