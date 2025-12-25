<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;

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
            $institutionId = $user->institute_id ?? session('active_institution_id');
        }
        $res = AuditLog::create([
            'user_id' => $user ? $user->id : null,
            'institution_id' => $institutionId,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
        // dd($res->id);
        Log::info("created ".$res->id);
    }
}