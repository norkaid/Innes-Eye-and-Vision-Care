<?php

namespace App\Services;

use App\Models\NotificationDelivery;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Single entry point for outbound notifications. Email is the only channel for now; new channels
 * (e.g. SMS) can be added here without touching the workflows. Delivery outcomes are recorded and
 * returned so the UI never claims a message was sent when it was not.
 */
class NotificationService
{
    public function __construct(private Settings $settings) {}

    public function mailConfigured(): bool
    {
        $mailer = config('mail.default');

        return (filled($mailer) && $mailer !== 'array') || app()->runningUnitTests();
    }

    public function customerEmailsEnabled(): bool
    {
        return $this->mailConfigured() && $this->settings->bool('notify_customer_emails');
    }

    /** Send to a customer email address. Returns true only if the mailer accepted the message. */
    public function toCustomer(string $email, BaseNotification $notification, ?object $subject = null): bool
    {
        if (! $this->customerEmailsEnabled()) {
            $this->record($notification, 'mail', $subject, 'skipped', 'Customer email notifications disabled or not configured');

            return false;
        }

        return $this->deliver($email, $notification, $subject);
    }

    public function toStaff(BaseNotification $notification, ?object $subject = null): bool
    {
        $address = $this->settings->get('notify_staff_email');
        if (! $address || ! $this->mailConfigured()) {
            $this->record($notification, 'mail', $subject, 'skipped', 'No staff notification address configured');

            return false;
        }

        return $this->deliver($address, $notification, $subject);
    }

    private function deliver(string $email, BaseNotification $notification, ?object $subject): bool
    {
        try {
            Notification::route('mail', $email)->notifyNow($notification);
            $this->record($notification, 'mail', $subject, 'sent');

            return true;
        } catch (\Throwable $e) {
            Log::warning('Notification delivery failed', ['type' => class_basename($notification), 'error' => $e->getMessage()]);
            $this->record($notification, 'mail', $subject, 'failed', mb_substr($e->getMessage(), 0, 480));

            return false;
        }
    }

    private function record(BaseNotification $notification, string $channel, ?object $subject, string $status, ?string $error = null): void
    {
        NotificationDelivery::create([
            'notification_type' => class_basename($notification),
            'channel' => $channel,
            'notifiable_type' => $subject ? class_basename($subject) : null,
            'notifiable_id' => $subject?->id ?? null,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
