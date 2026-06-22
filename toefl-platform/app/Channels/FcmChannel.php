<?php

namespace App\Channels;

use App\Models\Notification\FcmToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $tokens = FcmToken::getUserTokens($notifiable->id);

        if (empty($tokens)) {
            Log::warning("No FCM tokens found for user {$notifiable->id}");
            return;
        }

        $fcmData = $notification->toFcm($notifiable);

        if (!$fcmData) {
            Log::warning("FCM data is null for notification " . get_class($notification));
            return;
        }

        $serverKey = config('services.firebase.server_key');
        $projectId = config('services.firebase.project_id');

        if (!$serverKey || !$projectId) {
            Log::error('Firebase credentials not configured');
            return;
        }

        // Prepare FCM HTTP v1 API payload
        $payload = [
            'message' => [
                'token' => $tokens[0], // Send to first active token
                'notification' => [
                    'title' => $fcmData['title'] ?? 'Notification',
                    'body' => $fcmData['body'] ?? '',
                    'icon' => $fcmData['icon'] ?? 'notification_icon',
                ],
                'data' => $fcmData['data'] ?? [],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'click_action' => $fcmData['data']['click_action'] ?? null,
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'category' => $fcmData['data']['type'] ?? null,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post(
                "https://fcm.googleapis.com/fcm/send",
                $payload
            );

            if ($response->successful()) {
                Log::info("FCM notification sent successfully to user {$notifiable->id}");

                // Update notification status if it has delivery tracking
                $this->updateDeliveryStatus($notifiable, $notification, 'sent');
            } else {
                Log::error("FCM notification failed: " . $response->body());
                $this->updateDeliveryStatus($notifiable, $notification, 'failed', $response->body());
            }
        } catch (\Exception $e) {
            Log::error("FCM notification error: " . $e->getMessage());
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
