<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AuditLogger
{
    /**
     * Log a system activity.
     *
     * @param string $action Short action name (e.g., "Login", "Create")
     * @param string $module Module name (e.g., "Auth", "Student")
     * @param string|null $description Detailed description
     * @param mixed $oldValues Optional: Previous state (for updates)
     * @param mixed $newValues Optional: New state (for updates)
     */
    public static function log($action, $module, $description = null, $oldValues = null, $newValues = null)
    {
        $user = Auth::user();
        $institutionId = null;
        
        // Attempt to determine context
        if ($user) {
            $val = $user->institute_id ?? session('active_institution_id');
            // FIX: Ensure we don't pass 'global' string to integer column
            if ($val !== 'global') {
                $institutionId = $val;
            }
        }

        $ip = Request::ip();
        $location = self::getLocation($ip);

        $res = AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'institution_id' => $institutionId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => $ip,
            'location_details' => $location, // Store fetched location
            'user_agent' => Request::userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
        
        Log::info("created ".$res->id);
    }

    /**
     * Resolve IP to Location using public API
     * Returns array ['country' => ..., 'city' => ...] or null
     */
    protected static function getLocation($ip)
    {
        // Skip local IPs
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return null;
        }

        try {
            // Using ip-api.com (Free for non-commercial use, rate limited)
            // Timeout set to 1s to prevent slowing down the user experience
            $response = Http::timeout(1)->get("http://ip-api.com/json/{$ip}");

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['status']) && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'city' => $data['city'] ?? null,
                        'region' => $data['regionName'] ?? null, // State/Province
                        'isp' => $data['isp'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail if API is unreachable to not break the app flow
            return null;
        }

        return null;
    }
}