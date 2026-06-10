<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\Student;
use App\Models\Invoice;
use Illuminate\Http\Request;

class MobileLookupApiController extends Controller
{
    public function notices(Request $request)
    {
        $user = $request->user();
        $institutionId = $user->institute_id;

        $query = Notice::withoutGlobalScopes()
            ->where('is_published', true)
            ->orderByDesc('published_at');

        if ($institutionId) {
            $query->where(function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId)->orWhereNull('institution_id');
            });
        }

        $audience = $user->hasRole('Student') ? 'student' : ($user->hasRole('Guardian') ? 'parent' : 'staff');
        $query->where(function ($q) use ($audience) {
            $q->where('audience', 'all')->orWhere('audience', $audience);
        });

        $notices = $query->limit(50)->get()->map(fn ($n) => [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->content ?? '',
            'type' => $n->type,
            'created_at' => ($n->published_at ?? $n->created_at)?->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $notices,
        ]);
    }

    public function feeLookup(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $user = $request->user();
        if (!$user->hasRole(['Teacher', 'School Admin', 'Head Officer', 'Super Admin', 'Accountant'])
            && !$user->can('invoice.view')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $term = $request->query('query');
        $institutionId = $user->institute_id;

        $studentsQuery = Student::query()
            ->where(function ($q) use ($term) {
                $q->where('admission_number', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            });

        if ($institutionId) {
            $studentsQuery->where('institution_id', $institutionId);
        }

        $students = $studentsQuery->limit(20)->get()->map(function ($student) {
            $invoices = Invoice::where('student_id', $student->id)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();

            $totalDue = $invoices->sum(fn ($inv) => max(0, (float) $inv->total_amount - (float) $inv->paid_amount));

            return [
                'student_id' => $student->id,
                'name' => $student->full_name,
                'admission_number' => $student->admission_number,
                'total_due' => (float) $totalDue,
                'status' => $totalDue > 0 ? 'due' : 'clear',
                'last_invoice' => $invoices->first()?->only(['id', 'invoice_number', 'status', 'total_amount', 'paid_amount']),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }
}
