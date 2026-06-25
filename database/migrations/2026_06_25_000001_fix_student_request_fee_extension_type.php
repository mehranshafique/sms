<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_requests')
            ->where('type', 'leave')
            ->whereNotNull('student_id')
            ->update(['type' => 'fee_extension']);
    }

    public function down(): void
    {
        DB::table('student_requests')
            ->where('type', 'fee_extension')
            ->where(function ($query) {
                $query->where('reason', 'like', '%dérogation%')
                    ->orWhere('reason', 'like', '%derogation%')
                    ->orWhere('reason', 'like', '%Dérogation%');
            })
            ->update(['type' => 'leave']);
    }
};
