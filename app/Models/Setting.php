<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        $normalized = is_bool($value)
            ? ($value ? '1' : '0')
            : (string) ($value ?? '');

        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $normalized]
        );
    }

    /**
     * Read an on/off setting safely across SQLite/MySQL/PDO value types.
     * Handles 1, "1", true, "true", "on", "yes", and "enabled".
     */
    public static function isEnabled(string $key, bool $default = false): bool
    {
        $value = static::getValue($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        return in_array(
            strtolower(trim((string) $value)),
            ['1', 'true', 'on', 'yes', 'enabled'],
            true
        );
    }
}
