<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends text messages through the provider set in SMS_DRIVER:
 * - "log": writes the message to storage/logs/laravel.log (for a PC; nothing is delivered)
 * - "arkesel", "hubtel" or "twilio": real delivery with the keys from .env
 * - empty or "none": SMS switched off
 * Messages contain only what the customer needs (reference, time, branch) – never medical details.
 */
class SmsService
{
    public function driver(): string
    {
        return strtolower((string) config('services.sms.driver', ''));
    }

    /** SMS can be sent (or, with the log driver, written to the log). */
    public function enabled(): bool
    {
        return in_array($this->driver(), ['log', 'arkesel', 'hubtel', 'twilio'], true);
    }

    /** Messages actually reach phones. */
    public function delivers(): bool
    {
        return $this->enabled() && $this->driver() !== 'log';
    }

    /**
     * Converts a Ghanaian or international number to digits with the country code, e.g.
     * "024 123 4567" or "+233 24 123 4567" → "233241234567". Returns null if unusable.
     */
    public static function normalise(?string $phone, string $countryCode = '233'): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '' || $digits === null) {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = $countryCode.substr($digits, 1);
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }

    /**
     * Sends one message. Throws on failure so the caller can record it.
     *
     * @return string "sent" or "logged"
     */
    public function send(string $phone, string $message): string
    {
        $to = self::normalise($phone, (string) config('services.sms.country_code', '233'));
        if (! $to) {
            throw new \RuntimeException('The phone number is not valid for SMS.');
        }
        $message = mb_substr($message, 0, 459);
        $sender = (string) config('services.sms.sender_id', 'InnesEye');

        switch ($this->driver()) {
            case 'log':
                Log::info('SMS (log driver – not delivered)', ['to' => $to, 'message' => $message]);

                return 'logged';

            case 'arkesel':
                $response = Http::withHeaders(['api-key' => (string) config('services.sms.arkesel_key')])
                    ->acceptJson()->timeout(15)
                    ->post('https://sms.arkesel.com/api/v2/sms/send', ['sender' => $sender, 'message' => $message, 'recipients' => [$to]]);
                if (! $response->successful() || $response->json('status') !== 'success') {
                    throw new \RuntimeException('Arkesel: '.mb_substr((string) ($response->json('message') ?? $response->status()), 0, 150));
                }

                return 'sent';

            case 'hubtel':
                $response = Http::timeout(15)->get('https://smsc.hubtel.com/v1/messages/send', [
                    'clientid' => config('services.sms.hubtel_client_id'),
                    'clientsecret' => config('services.sms.hubtel_client_secret'),
                    'from' => $sender, 'to' => $to, 'content' => $message,
                ]);
                if (! $response->successful()) {
                    throw new \RuntimeException('Hubtel: HTTP '.$response->status());
                }

                return 'sent';

            case 'twilio':
                $sid = (string) config('services.sms.twilio_sid');
                $response = Http::asForm()->timeout(15)->withBasicAuth($sid, (string) config('services.sms.twilio_token'))
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                        'From' => config('services.sms.twilio_from'), 'To' => '+'.$to, 'Body' => $message,
                    ]);
                if (! $response->successful()) {
                    throw new \RuntimeException('Twilio: '.mb_substr((string) ($response->json('message') ?? $response->status()), 0, 150));
                }

                return 'sent';
        }

        throw new \RuntimeException('SMS is switched off (SMS_DRIVER is not set).');
    }
}
