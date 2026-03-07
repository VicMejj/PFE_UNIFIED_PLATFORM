<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'category',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a setting value by key
     */
    public static function getValue($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? self::castValue($setting->value, $setting->type) : $default;
    }

    /**
     * Set a setting value
     */
    public static function setValue($key, $value, $type = 'string', $category = null)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'category' => $category]
        );
    }

    /**
     * Cast value based on type
     */
    private static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Scope to get settings by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to get company settings
     */
    public function scopeCompany($query)
    {
        return $query->where('category', 'company');
    }

    /**
     * Scope to get system settings
     */
    public function scopeSystem($query)
    {
        return $query->where('category', 'system');
    }

    /**
     * Scope to get module settings
     */
    public function scopeModules($query)
    {
        return $query->where('category', 'modules');
    }
}
