<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Controllers\Api\V1\AttendanceApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceKioskController extends Controller
{
    public function show()
    {
        $this->ensureKioskRole();

        $institutionName = optional(Auth::user()->institute)->name
            ?? config('app.name');

        return view('attendance.kiosk', [
            'institutionName' => $institutionName,
            'scanUrl' => route('attendance.kiosk.scan'),
        ]);
    }

    public function scan(Request $request, AttendanceApiController $attendanceApi)
    {
        $this->ensureKioskRole();

        $request->validate([
            'uid' => 'required|string|max:191',
            'method' => 'nullable|string|max:40',
        ]);

        $request->merge([
            'method' => $request->input('method', 'qr'),
            'device_id' => $request->input('device_id', 'web-kiosk'),
            'purpose' => 'attendance',
        ]);

        return $attendanceApi->store($request);
    }

    private function ensureKioskRole(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->hasRole([
            RoleEnum::GATE_ATTENDANT->value,
            RoleEnum::SUPER_ADMIN->value,
            RoleEnum::HEAD_OFFICER->value,
            RoleEnum::SCHOOL_ADMIN->value,
            'Guard',
        ]), 403);
    }
}
