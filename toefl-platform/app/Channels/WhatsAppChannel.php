<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class WhatsAppChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        $message = $notification->toWhatsApp($notifiable);
        
        if (!$message) {
            return;
        }

        $config = config('services.whatsapp');
        
        if (!$config || empty($config['api_key'])) {
            \Log::warning('WhatsApp API not configured');
            return;
        }

        $phone = $notifiable->routeNotificationFor('whatsapp');
        
        if (!$phone) {
            \Log::warning('No WhatsApp phone number for user ' . $notifiable->getKey());
            return;
        }

        // Format phone number (remove +, spaces, dashes)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present
        if (!str_starts_with($phone, '62')) {
            $phone = '62' . ltrim($phone, '0');
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json',
            ])->post($config['endpoint'] ?? 'https://api.fonnte.com/send', [
                'target' => $phone,
                'message' => $message->content,
                'countryCode' => '62',
            ]);

            if ($response->successful()) {
                \Log::info('WhatsApp sent to ' . $phone);
            } else {
                \Log::error('WhatsApp failed: ' . $response->body());
                throw new \Exception('WhatsApp API error: ' . $response->status());
            }
        } catch (\Exception $e) {
            \Log::error('WhatsApp send error: ' . $e->getMessage());
            throw $e;
        }
    }
}
