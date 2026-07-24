<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function getGcashSettings(): array
    {
        return [
            'name' => static::getValue('gcash_name'),
            'number' => static::getValue('gcash_number'),
            'qr_url' => static::getValue('gcash_qr_url'),
        ];
    }

    public static function getBusinessProfile(): array
    {
        return [
            'business_name' => static::getValue('business_name', 'Ferosa Landscaping'),
            'business_address' => static::getValue('business_address', 'A. Arellano Ave. Mulawin, Orani, Bataan 2112'),
            'business_phone' => static::getValue('business_phone'),
            'business_email' => static::getValue('business_email'),
            'business_hours' => static::getValue('business_hours'),
            'service_area' => static::getValue('service_area', 'Orani, Bataan'),
            'booking_notice' => static::getValue('booking_notice', 'Appointments must be booked at least 24 hours in advance.'),
            'service_guarantee' => static::getValue('service_guarantee'),
            'cancellation_policy' => static::getValue('cancellation_policy'),
        ];
    }

    public static function setBusinessProfile(array $profile): void
    {
        foreach ($profile as $key => $value) {
            static::setValue($key, filled($value) ? trim((string) $value) : null);
        }
    }
}
