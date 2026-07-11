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
     * Resolve the platform-wide provider used when a school selects "System Default".
     */
    public static function resolveSystemProvider(string $channel): string
    {
        $key = $channel === 'whatsapp' ? 'whatsapp_provider' : 'sms_provider';
        $fallback = $channel === 'whatsapp'
            ? config('sms.whatsapp_default', 'meta')
            : config('sms.default', 'mobishastra');

        return self::get(null, $key, $fallback);
    }

    /**
     * Resolve selected provider, gateway driver, and credential scope for SMS/WhatsApp.
     *
     * @return array{selected: string, resolved: string, credentials_institution_id: ?int, deduct_credits: bool}
     */
    public static function resolveMessagingContext(?int $institutionId, string $channel): array
    {
        $providerKey = $channel === 'whatsapp' ? 'whatsapp_provider' : 'sms_provider';
        $selected = self::get($institutionId, $providerKey, 'system');
        $useSystem = $selected === 'system';

        return [
            'selected' => $selected,
            'resolved' => $useSystem ? self::resolveSystemProvider($channel) : $selected,
            'credentials_institution_id' => $useSystem ? null : $institutionId,
            'deduct_credits' => $useSystem,
        ];
    }

    /**
     * Human-readable provider label for configuration / test UI.
     */
    public static function displayProviderName(?int $institutionId, string $channel): string
    {
        $context = self::resolveMessagingContext($institutionId, $channel);

        return ucfirst($context['resolved']);
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

    /**
     * Check if a specific period is open for marks entry.
     * Periods are stored as a JSON array in 'active_periods'.
     */
    public static function isPeriodOpen($institutionId, $periodKey)
    {
        $activePeriods = json_decode(self::get($institutionId, 'active_periods', '[]'), true);
        return in_array($periodKey, $activePeriods);
    }
}