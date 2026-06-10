<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SmsTemplate;

return new class extends Migration
{
    public function up(): void
    {
        if (!class_exists(SmsTemplate::class)) {
            return;
        }

        SmsTemplate::updateOrCreate(
            ['event_key' => 'student_absent', 'institution_id' => null],
            [
                'name' => 'Student Absence Alert',
                'body' => 'Dear Parent, your child $StudentName was marked absent from $SchoolName on $Date. Please contact the school if you have any questions.',
                'available_tags' => '$StudentName, $Date, $SchoolName, $ParentName',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        if (!class_exists(SmsTemplate::class)) {
            return;
        }

        SmsTemplate::where('event_key', 'student_absent')
            ->whereNull('institution_id')
            ->delete();
    }
};
