<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SmsTemplate;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $templates = [
            [
                'event_key' => 'payment_received',
                'name' => 'Payment Received',
                'body' => 'Dear Parent, payment of $Amount for $StudentName has been received. Remaining Balance: $Balance. Thank you, $SchoolName.',
                'available_tags' => '$StudentName, $Amount, $Balance, $SchoolName, $Date, $TransactionID',
                'is_active' => true,
            ],
            [
                'event_key' => 'student_welcome',
                'name' => 'Student Welcome',
                'body' => 'Welcome $Name to $SchoolName! Your login details sent to email. Contact admin for support.',
                'available_tags' => '$Name, $SchoolName, $Email, $Url',
                'is_active' => true,
            ],
            [
                'event_key' => 'head_officer_welcome',
                'name' => 'Head Officer Welcome',
                'body' => 'Hello $Name, you are appointed as Head Officer at $SchoolName. Login: $Url | Pass: $Password',
                'available_tags' => '$Name, $SchoolName, $Email, $Password, $Url',
                'is_active' => true,
            ],
            [
                'event_key' => 'institution_created',
                'name' => 'Institution Created',
                'body' => 'New Institute $SchoolName Registered. Admin: $Name. Creds sent via Email.',
                'available_tags' => '$Name, $SchoolName, $Email, $Password',
                'is_active' => true,
            ],
            [
                'event_key' => 'low_balance',
                'name' => 'Low SMS Balance Warning',
                'body' => 'Alert: Your school $SchoolName is running low on SMS credits. Please recharge immediately.',
                'available_tags' => '$SchoolName, $Credits',
                'is_active' => true,
            ],
            [
                'event_key' => 'candidate_added',
                'name' => 'Candidate Registration',
                'body' => 'Your child $StudentName has submitted a candidacy for the position of $Position.',
                'available_tags' => '$StudentName, $Position',
                'is_active' => true,
            ],

        ];

        foreach ($templates as $tmpl) {
            SmsTemplate::updateOrCreate(
                ['event_key' => $tmpl['event_key'], 'institution_id' => null], // Null = Global Template
                $tmpl
            );
        }
    }
}