<?php

use App\Models\InstitutionSetting;
use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public const EVENT_KEY = 'homework_published';

    public function up(): void
    {
        SmsTemplate::updateOrCreate(
            ['institution_id' => null, 'event_key' => self::EVENT_KEY],
            [
                'name' => 'New Homework Published',
                'body' => 'New homework for $StudentName ($ClassName). Subject: $Subject. Title: $Title. Due $Deadline. To see the details or ask a question, use our WhatsApp chatbot. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $Title, $Subject, $ClassName, $Deadline, $SchoolName',
                'is_active' => true,
            ]
        );

        // WhatsApp-first by default; schools can change this per event in
        // Configuration → Notifications. Only set the global fallback so we never
        // overwrite a school's existing choice.
        $key = 'notify_' . self::EVENT_KEY;

        if (! InstitutionSetting::whereNull('institution_id')->where('key', $key)->exists()) {
            InstitutionSetting::set(null, $key, json_encode([
                'sms' => false,
                'whatsapp' => true,
                'email' => false,
                'system' => true,
            ]), 'notifications');
        }
    }

    public function down(): void
    {
        SmsTemplate::whereNull('institution_id')->where('event_key', self::EVENT_KEY)->delete();
        InstitutionSetting::whereNull('institution_id')->where('key', 'notify_' . self::EVENT_KEY)->delete();
    }
};
