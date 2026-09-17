<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class StoreSettings
{
    public static function allPublic(): array
    {
        return Cache::remember('store.public_settings', 3600, function () {
            return Setting::query()
                ->where('is_public', true)
                ->pluck('value', 'key')
                ->map(fn ($value) => is_string($value) ? json_decode($value, true) : $value)
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = Cache::remember('store.settings', 3600, function () {
            return Setting::query()
                ->pluck('value', 'key')
                ->map(fn ($value) => is_string($value) ? json_decode($value, true) : $value)
                ->all();
        });

        return $settings[$key] ?? $default;
    }

    public static function put(string $key, mixed $value, string $group = 'general', string $type = 'text', bool $public = true): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['group' => $group, 'type' => $type, 'is_public' => $public, 'value' => $value],
        );

        Cache::forget('store.settings');
        Cache::forget('store.public_settings');
    }
}
