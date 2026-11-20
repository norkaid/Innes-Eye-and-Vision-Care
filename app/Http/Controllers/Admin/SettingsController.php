<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Notifications\TestNotification;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use App\Services\NotificationService;
use App\Services\Settings;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * General (public) details require settings.general; booking rules and notification
 * settings require settings.system. Each section is checked on the server.
 */
class SettingsController extends Controller
{
    public function edit(Settings $settings)
    {
        return view('admin.settings.edit', ['values' => $settings->all()]);
    }

    public function update(Request $request, Settings $settings, AuditLogger $audit, ImageUploader $images)
    {
        $section = $request->input('section');
        abort_unless(in_array($section, ['general', 'logo', 'images', 'shop', 'system'], true), 422);
        $this->authorize($section === 'system' ? 'settings.system' : 'settings.general');

        if ($section === 'logo') {
            $request->validate([
                'logo' => ImageUploader::rules(),
                'logo_media_id' => ['nullable', 'integer', 'exists:media,id'],
                'remove_logo' => ['boolean'],
            ]);
            $path = $settings->get('logo_path');
            if ($request->hasFile('logo')) {
                $path = $images->store($request->file('logo'), 'logo', $settings->get('business_name').' logo');
            } elseif ($id = $request->integer('logo_media_id')) {
                $path = Media::findOrFail($id)->path;
            } elseif ($request->boolean('remove_logo')) {
                $path = null;
            }
            $settings->put(['logo_path' => $path]);
            $audit->log('settings.updated', null, ['section' => 'logo']);

            return redirect()->route('admin.settings.edit')->with('success', $path ? 'Logo saved.' : 'Logo removed; the standard logo is shown.');
        }

        if ($section === 'shop') {
            $data = $request->validate([
                'shop_delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:10000'],
                'shop_delivery_note' => ['nullable', 'string', 'max:300'],
            ]);
            $data['shop_pickup_enabled'] = $request->boolean('shop_pickup_enabled');
            $data['shop_delivery_enabled'] = $request->boolean('shop_delivery_enabled');
            $data['shop_delivery_fee'] = round((float) ($data['shop_delivery_fee'] ?? 0), 2);
            $settings->put(array_intersect_key($data, array_flip(Settings::SHOP_KEYS)));
            $audit->log('settings.updated', null, ['section' => 'shop']);

            return redirect()->route('admin.settings.edit')->with('success', 'Shop settings saved.');
        }

        if ($section === 'images') {
            $choice = ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                if ($value !== 'none' && ! Media::whereKey($value)->exists()) {
                    $fail('Choose an image from the media library.');
                }
            }];
            $data = $request->validate(['hero_image' => $choice, 'about_image' => $choice]);
            $paths = [];
            foreach (['hero_image' => 'hero_image_path', 'about_image' => 'about_image_path'] as $field => $key) {
                if (array_key_exists($field, $data) && $data[$field] !== null) {
                    $paths[$key] = $data[$field] === 'none' ? null : Media::whereKey($data[$field])->value('path');
                }
            }
            $settings->put($paths);
            $audit->log('settings.updated', null, ['section' => 'images', 'keys' => array_keys($paths)]);

            return redirect()->route('admin.settings.edit')->with('success', 'Website images saved.');
        }

        if ($section === 'general') {
            $data = $request->validate([
                'business_name' => ['required', 'string', 'max:150'],
                'tagline' => ['nullable', 'string', 'max:200'],
                'contact_email' => ['nullable', 'email:rfc', 'max:255'],
                'contact_phone' => ['nullable', 'string', 'max:30'],
                'whatsapp' => ['nullable', 'string', 'max:30'],
                'facebook_url' => ['nullable', 'url:https', 'max:255'],
                'instagram_url' => ['nullable', 'url:https', 'max:255'],
                'response_time_note' => ['nullable', 'string', 'max:300'],
                'about_intro' => ['nullable', 'string', 'max:5000'],
                'mission' => ['nullable', 'string', 'max:1000'],
                'vision' => ['nullable', 'string', 'max:1000'],
                'values' => ['nullable', 'string', 'max:1000'],
            ]);
            $data = array_intersect_key($data, array_flip(Settings::GENERAL_KEYS));
        } else {
            $data = $request->validate([
                'booking_lead_time_hours' => ['required', 'integer', 'min:0', 'max:720'],
                'booking_window_days' => ['required', 'integer', 'min:1', 'max:365'],
                'slot_duration_minutes' => ['required', 'integer', 'in:10,15,20,30,45,60,90,120'],
                'slot_capacity' => ['required', 'integer', 'min:1', 'max:20'],
                'cancellation_cutoff_hours' => ['required', 'integer', 'min:0', 'max:720'],
                'notify_customer_emails' => ['boolean'],
                'notify_staff_email' => ['nullable', 'email:rfc', 'max:255'],
                'consultation_fee' => ['nullable', 'numeric', 'min:0', 'max:100000'],
                'deposit_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
                'notify_customer_sms' => ['boolean'],
            ]);
            $data['notify_customer_emails'] = $request->boolean('notify_customer_emails');
            $data['notify_customer_sms'] = $request->boolean('notify_customer_sms');
            // Fields left out keep their current value.
            foreach (['consultation_fee', 'deposit_percent'] as $key) {
                if (! isset($data[$key])) {
                    unset($data[$key]);
                }
            }
            $data = array_intersect_key($data, array_flip(Settings::SYSTEM_KEYS));
        }

        $settings->put($data);
        $audit->log('settings.updated', null, ['section' => $section, 'keys' => array_keys($data)]);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }

    /** Sends a test email and/or SMS so staff can check that customer notifications really arrive. */
    public function test(Request $request, SmsService $sms, NotificationService $notifications)
    {
        $data = $request->validate([
            'test_email' => ['nullable', 'email:rfc', 'max:255', 'required_without:test_phone'],
            'test_phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-()]{7,20}$/'],
        ]);
        $results = [];

        if (! empty($data['test_email'])) {
            try {
                Notification::route('mail', $data['test_email'])->notifyNow(new TestNotification);
                $results[] = config('mail.default') === 'log'
                    ? 'Email written to storage/logs/laravel.log only (MAIL_MAILER=log). Set up SMTP in .env to really send emails.'
                    : "Test email sent to {$data['test_email']}. Check the inbox (and spam folder).";
            } catch (\Throwable $e) {
                $results[] = 'Email failed: '.mb_substr($e->getMessage(), 0, 200);
            }
        }
        if (! empty($data['test_phone'])) {
            if (! $sms->enabled()) {
                $results[] = 'SMS is switched off: set SMS_DRIVER and the provider keys in .env.';
            } else {
                try {
                    $result = $sms->send($data['test_phone'], app(Settings::class)->get('business_name').': test message. SMS notifications are working.');
                    $results[] = $result === 'logged'
                        ? 'SMS written to storage/logs/laravel.log only (SMS_DRIVER=log). Choose a real provider to send texts.'
                        : 'Test SMS sent to '.$data['test_phone'].'.';
                } catch (\Throwable $e) {
                    $results[] = 'SMS failed: '.mb_substr($e->getMessage(), 0, 200);
                }
            }
        }

        return redirect()->route('admin.settings.edit')->with('success', implode(' ', $results));
    }
}
