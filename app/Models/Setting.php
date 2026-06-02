<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'group'];

    /**
     * Lire un paramètre par clé (avec cache 10 min).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Écrire un paramètre et invalider le cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }

    /**
     * Tous les paramètres d'un groupe, indexés par clé.
     */
    public static function group(string $group): \Illuminate\Support\Collection
    {
        return static::where('group', $group)->get()->keyBy('key');
    }
}
