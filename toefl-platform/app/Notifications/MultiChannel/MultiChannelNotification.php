<?php

namespace App\Notifications\MultiChannel;

use App\Models\Notification\DndQueue;
use App\Models\Notification\NotificationPreference;
use App\Models\Notification\NotificationRateLimit;
use App\Models\Notification\UserDndSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

abstract class MultiChannelNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Notification category (payment, assignment, forum, streak, study_plan, attendance).
     */
    protected string $category = 'general';

    /**
     * Event type for preferences.
     */
    protected string $eventType = 'general';

    /**
     * Whether this notification is urgent (bypasses DND).
     */
    protected bool $isUrgent = false;

    /**
     * Rate limit type for this notification.
     */
    protected ?string $rateLimitType = null;

    /**
     * Template variables to replace in messages.
     */
    protected array $templateVariables = [];

    /**
     * Max retry attempts.
     */
    protected int $maxRetries = 3;

    /**
     * Get the notification's delivery channels based on user preferences.
     */
    public function via(object $notifiable): array
    {
        $preference = NotificationPreference::getOrCreate(
            $notifiable->id,
            $this->category,
            $this->eventType
        );

        return $preference->getEnabledChannels();
    }

    /**
     * Check if notification should be sent considering DND and rate limits.
     */
    protected function shouldSendNow(object $notifiable): bool
    {
        // Check rate limit
        if ($this->rateLimitType && !NotificationRateLimit::canSend($notifiable->id, $this->rateLimitType)) {
            Log::info("Rate limit exceeded for user {$notifiable->id} - type: {$this->rateLimitType}");
            return false;
        }

        // Check DND
        $dndSetting = UserDndSetting::getOrCreate($notifiable->id);

        if ($dndSetting->isCurrentlyActive() && !$this->isUrgent) {
            // Queue for later if not urgent
            $this->queueForDnd($notifiable, $dndSetting);
            return false;
        }

        return true;
    }

    /**
     * Queue notification for sending after DND ends.
     */
    protected function queueForDnd(object $notifiable, UserDndSetting $dndSetting): void
    {
        // Calculate when DND ends
        $now = now();
        $endTime = \Carbon\Carbon::parse($dndSetting->end_time);

        // If end time is before current time, it means DND ends tomorrow
        if ($endTime->lessThan($now)) {
            $endTime->addDay();
        }

        $scheduledTime = $endTime->setTimezone($now->timezone);

        DndQueue::queue(
            $notifiable->id,
            static::class,
            $this->toArray($notifiable),
            $this->category,
            $this->isUrgent,
            $scheduledTime
        );

        Log::info("Notification queued for user {$notifiable->id} due to DND until {$scheduledTime}");
    }

    /**
     * Render template with variables.
     */
    protected function renderTemplate(string $template): string
    {
        foreach ($this->templateVariables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }

        return $template;
    }

    /**
     * Increment rate limit counter after successful send.
     */
    protected function incrementRateLimit(object $notifiable): void
    {
        if ($this->rateLimitType) {
            NotificationRateLimit::increment($notifiable->id, $this->rateLimitType);
        }
    }

    /**
     * Handle retry logic with exponential backoff.
     */
    public function retryUntil(): ?\DateTime
    {
        return now()->addMinutes(pow(2, $this->maxRetries) * 5); // Exponential backoff: 10, 20, 40 minutes
    }

    /**
     * Get retry delay in seconds.
     */
    public function backoff(): array
    {
        return [10, 20, 40]; // Exponential backoff delays
    }

    /**
     * Handle failed notification.
     */
    public function failed(object $notifiable, \Throwable $exception): void
    {
        Log::error("Notification failed for user {$notifiable->id}: " . $exception->getMessage(), [
            'notification' => static::class,
            'category' => $this->category,
        ]);

        // Update notification status in database if applicable
        if (method_exists($notifiable, 'notifications')) {
            $notifiable->notifications()
                ->where('type', static::class)
                ->where('data->notification_id', $this->id ?? null)
                ->update([
                    'delivery_status' => 'failed',
                    'error_message' => $exception->getMessage(),
                ]);
        }
    }

    /**
     * Get the array representation of the notification.
     */
    abstract public function toArray(object $notifiable): array;

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable)
    {
        // Override in child classes
        return null;
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable)
    {
        // Override in child classes
        return null;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable)
    {
        // Override in child classes
        return null;
    }

    /**
     * Get the WhatsApp representation of the notification.
     */
    public function toWhatsApp(object $notifiable)
    {
        // Override in child classes
        return null;
    }
}
