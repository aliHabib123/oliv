<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a platform setting value by key.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("platform_setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting?->value ?? $default;
        });
    }

    /**
     * Get an encrypted platform setting value.
     */
    public static function getEncryptedValue(string $key, mixed $default = null): mixed
    {
        $value = static::getValue($key);
        if (! $value) {
            return $default;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return $value; // Return raw if not encrypted (backwards compat)
        }
    }

    /**
     * Set a platform setting value.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("platform_setting.{$key}");
    }

    /**
     * Set an encrypted platform setting value.
     */
    public static function setEncryptedValue(string $key, ?string $value): void
    {
        $encrypted = $value ? Crypt::encryptString($value) : null;
        static::setValue($key, $encrypted);
    }

    /**
     * Get Shopify app credentials.
     */
    public static function getShopifyAppCredentials(): array
    {
        return [
            'client_id' => static::getValue('shopify_app_client_id'),
            'client_secret' => static::getEncryptedValue('shopify_app_client_secret'),
            'redirect_uri' => static::getValue('shopify_app_redirect_uri'),
        ];
    }
}
