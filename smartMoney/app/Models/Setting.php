<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'description'];

    protected static function booted(): void
    {
        static::saved(fn (Setting $setting) => Cache::forget("setting:{$setting->key}"));
        static::deleted(fn (Setting $setting) => Cache::forget("setting:{$setting->key}"));
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key) {
            return static::where('key', $key)->value('value');
        }) ?? $default;
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $value = static::get($key);
        if ($value === null) return $default;
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = static::get($key);
        if ($value === null) return $default;
        return (int) $value;
    }

    public static function set(string $key, ?string $value, ?string $description = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_filter(['value' => $value, 'description' => $description], fn($v) => $v !== null)
        );
    }
}
