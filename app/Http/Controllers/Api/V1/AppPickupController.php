<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StudentPickup;
use Illuminate\Support\Facades\Auth;

class AppPickupController extends Controller
{
    /**
     * Fetch Live Pending Pickups for the Teacher's Class
     * Endpoint: GET /api/v1/pickup/pending
     */
    public function getPendingPickups(Request $request)
    {
        $user = Auth::user(); // The teacher logged into the mobile app

        // Fetch pending pickups for the institution (or filter by teacher's specific class)
        $pickups = StudentPickup::with(['student.enrollments.classSection'])
            ->where('institution_id', $user->institute_id)
            ->where('status', 'pending')
            ->whereDate('created_at', today())
            ->latest()
            ->get()
            ->map(function($pickup) {
                $class = $pickup->student->enrollments->first()->classSection->name ?? 'N/A';
                return [
                    'pickup_id' => $pickup->id,
                    'student_name' => $pickup->student->full_name,
                    'class_name' => $class,
                    'requested_by' => $pickup->requested_by,
                    'time' => $pickup->created_at->format('H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pickups
        ]);
    }

    /**
     * Manual Approval from the Teacher's Screen
     */
    public function approvePickup(Request $request)
    {
        $request->validate(['pickup_id' => 'required']);

        $pickup = StudentPickup::findOrFail($request->pickup_id);
        $pickup->update(['status' => 'completed', 'scanned_at' => now(), 'scanned_by_device' => 'TEACHER_APP']);

        return response()->json(['success' => true, 'message' => 'Student Released successfully!']);
    }
}