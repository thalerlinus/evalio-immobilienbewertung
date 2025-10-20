<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builders\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property string $key
 * @property string|null $label
 * @property string|null $value
 */
class ContactSetting extends Model
{
    protected $fillable = [
        'key',
        'label',
        'type',
        'value',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public static function findValue(string $key, ?string $default = null): ?string
    {
        $cacheKey = sprintf('contact_settings.%s', $key);

        return Cache::rememberForever($cacheKey, function () use ($key, $default) {
            /** @var self|null $setting */
            $setting = self::query()->where('key', $key)->first();

            return $setting?->value ?? $default;
        });
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string|null>
     */
    public static function values(array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = self::findValue($key);
        }

        return $values;
    }

    protected static function booted(): void
    {
        static::saved(function (self $setting): void {
            Cache::forget(sprintf('contact_settings.%s', $setting->key));
        });

        static::deleted(function (self $setting): void {
            Cache::forget(sprintf('contact_settings.%s', $setting->key));
        });
    }
}
