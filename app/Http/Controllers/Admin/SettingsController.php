<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\AuditLogger;
use App\Services\ImageUploader;
use App\Services\Settings;
use Illuminate\Http\Request;

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
        abort_unless(in_array($section, ['general', 'logo', 'system'], true), 422);
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
            ]);
            $data['notify_customer_emails'] = $request->boolean('notify_customer_emails');
            $data = array_intersect_key($data, array_flip(Settings::SYSTEM_KEYS));
        }

        $settings->put($data);
        $audit->log('settings.updated', null, ['section' => $section, 'keys' => array_keys($data)]);

        return redirect()->route('admin.settings.edit')->with('success', 'Settings saved.');
    }
}
