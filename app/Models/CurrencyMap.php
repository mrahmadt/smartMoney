<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Ai\Agents\ResolveCurrency;
use App\Models\Setting;

class CurrencyMap extends Model
{
    protected $fillable = ['code', 'name', 'aliases'];

    protected $casts = [
        'aliases' => 'array',
    ];

    /**
     * Lookup ISO 4217 code from any text (code, name, or alias).
     * Returns the 3-letter ISO code or null if not found.
     */
    public static function lookup(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Fast path: already an ISO code
        $upper = strtoupper($text);
        if (preg_match('/^[A-Z]{3}$/', $upper)) {
            $exists = Cache::remember("currency_code_{$upper}", 3600, function () use ($upper) {
                return static::where('code', $upper)->exists();
            });
            if ($exists) {
                return $upper;
            }
        }

        // Search aliases (cached for 1 hour)
        $allMaps = Cache::remember('currency_maps_all', 3600, function () {
            return static::all();
        });

        foreach ($allMaps as $map) {
            if (strcasecmp($map->code, $text) === 0) {
                return $map->code;
            }
            if (strcasecmp($map->name, $text) === 0) {
                return $map->code;
            }
            if (is_array($map->aliases)) {
                foreach ($map->aliases as $alias) {
                    if (strcasecmp($alias, $text) === 0) {
                        return $map->code;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Add a new alias to an existing currency code.
     */
    public static function addAlias(string $code, string $alias): void
    {
        $map = static::where('code', strtoupper($code))->first();
        if (!$map) {
            return;
        }

        $aliases = $map->aliases ?? [];
        if (!in_array($alias, $aliases)) {
            $aliases[] = $alias;
            $map->update(['aliases' => $aliases]);
            Cache::forget('currency_maps_all');
        }
    }

    /**
     * Clear the currency map cache.
     */
    public static function clearCache(): void
    {
        Cache::forget('currency_maps_all');
    }

    /**
     * Resolve a currency text to ISO 4217 code.
     * First checks the database, then falls back to AI agent.
     * If AI resolves it, adds the text as an alias for future lookups.
     */
    public static function resolve(string $text): ?string
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Try local lookup first
        $code = static::lookup($text);
        if ($code) {
            return $code;
        }

        // AI fallback
        try {
            $agent = new ResolveCurrency();
            $agent->currencyText = $text;

            $model = Setting::get('parsesms_category_model');
            $response = $agent->prompt("Resolve currency: {$text}", model: $model);
            $output = json_decode($response->text, true);

            Log::debug('ResolveCurrency AI response', ['text' => $text, 'output' => $output]);

            if (json_last_error() === JSON_ERROR_NONE && !empty($output['code'])) {
                $code = strtoupper($output['code']);
                if (preg_match('/^[A-Z]{3}$/', $code)) {
                    // Add as alias for future lookups
                    static::addAlias($code, $text);
                    Log::info('ResolveCurrency: added alias', ['code' => $code, 'alias' => $text]);
                    return $code;
                }
            }
        } catch (\Exception $e) {
            Log::warning('ResolveCurrency AI failed', ['text' => $text, 'error' => $e->getMessage()]);
        }

        return null;
    }
}
