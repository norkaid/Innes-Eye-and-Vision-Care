<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Typed access to key/value business settings with config fallbacks and a short cache.
 */
class Settings
{
    private const CACHE_KEY = 'innes.business_settings';

    /** Keys only Super Admins (settings.system) may change. */
    public const SYSTEM_KEYS = [
        'booking_lead_time_hours', 'booking_window_days', 'slot_duration_minutes', 'slot_capacity',
        'cancellation_cutoff_hours', 'notify_customer_emails', 'notify_staff_email',
    ];

    public const GENERAL_KEYS = [
        'business_name', 'tagline', 'contact_email', 'contact_phone', 'whatsapp', 'facebook_url',
        'instagram_url', 'response_time_note', 'about_intro', 'mission', 'vision', 'values',
    ];

    /** @var array<string,mixed>|null */
    private ?array $values = null;

    public function all(): array
    {
        if ($this->values === null) {
            $stored = [];
            try {
                $stored = Cache::remember(self::CACHE_KEY, 300, fn () => Schema::hasTable('business_settings')
                    ? BusinessSetting::query()->pluck('value', 'key')->all()
                    : []);
            } catch (\Throwable) {
                $stored = [];
            }
            $this->values = array_merge(config('innes.settings_defaults'), array_filter($stored, fn ($v) => $v !== null));
        }

        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? $default;

        return $value === '' ? $default : $value;
    }

    public function int(string $key): int
    {
        return (int) $this->get($key, config("innes.settings_defaults.$key", 0));
    }

    public function bool(string $key): bool
    {
        return filter_var($this->get($key, false), FILTER_VALIDATE_BOOL);
    }

    /** @param array<string,mixed> $values */
    public function put(array $values): void
    {
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            BusinessSetting::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
        }
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }
}
