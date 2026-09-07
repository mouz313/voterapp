<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class FinanceSetting extends Model
{
    protected $table = 'finance_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function get(string $key, $default = null)
    {
        $setting = static::find($key);
        return $setting ? $setting->value : $default;
    }

    public static function getSetting(string $key, $default = null)
    {
        return static::get($key, $default);
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function verifyPassword(string $password): bool
    {
        $hash = static::get('finance_password_hash');
        if (empty($hash)) {
            // Fallback default
            return $password === 'voter123';
        }

        return Hash::check($password, $hash);
    }

    public static function setPassword(string $newPassword): void
    {
        static::set('finance_password_hash', Hash::make($newPassword));
    }
}
