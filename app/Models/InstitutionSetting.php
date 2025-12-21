<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionSetting extends Model
{
    use HasFactory;

    protected $fillable = ['institution_id', 'key', 'value', 'group'];

    /**
     * Helper to get a setting value with a default fallback.
     */
    public static function get($institutionId, $key, $default = null)
    {
        $setting = self::where('institution_id', $institutionId)
                       ->where('key', $key)
                       ->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Helper to set a setting value.
     */
    public static function set($institutionId, $key, $value, $group = 'general')
    {
        return self::updateOrCreate(
            ['institution_id' => $institutionId, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }
}