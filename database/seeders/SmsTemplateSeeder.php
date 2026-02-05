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
            // Payment
            [
                'event_key' => 'payment_received',
                'name' => 'Payment Received',
                'body' => 'Dear Parent, payment of $Amount for $StudentName has been received. Remaining Balance: $Balance. Thank you, $SchoolName.',
                'available_tags' => '$StudentName, $Amount, $Balance, $SchoolName, $Date, $TransactionID',
                'is_active' => true,
            ],
            
            // Student Welcome
            [
                'event_key' => 'student_welcome',
                'name' => 'Student Welcome',
                'body' => 'Welcome $Name to $SchoolName! Your Admission No: $Shortcode. Login using this ID. Password: $Password. URL: $Url',
                'available_tags' => '$Name, $SchoolName, $Email, $Url, $Shortcode, $Password',
                'is_active' => true,
            ],
            
            // Staff / Teacher Welcome (New)
            [
                'event_key' => 'staff_welcome', // Used by NotificationService logic
                'name' => 'Staff Welcome',
                'body' => 'Hello $Name, welcome to the team at $SchoolName! Login ID: $Email | Pass: $Password | URL: $Url',
                'available_tags' => '$Name, $SchoolName, $Email, $Password, $Url',
                'is_active' => true,
            ],
            [
                'event_key' => 'teacher_welcome', // Specific for teachers if needed
                'name' => 'Teacher Welcome',
                'body' => 'Hello $Name, welcome to $SchoolName as a Teacher. Login: $Email | Pass: $Password',
                'available_tags' => '$Name, $SchoolName, $Email, $Password',
                'is_active' => true,
            ],

            // Head Officer / Admin
            [
                'event_key' => 'head_officer_welcome',
                'name' => 'Head Officer Welcome',
                'body' => 'Hello $Name, you are appointed as Head Officer at $SchoolName. Login: $Url | Pass: $Password',
                'available_tags' => '$Name, $SchoolName, $Email, $Password, $Url',
                'is_active' => true,
            ],
            
            // Institution Creation (Super Admin Trigger)
            [
                'event_key' => 'institution_created',
                'name' => 'Institution Created',
                'body' => 'New Institute $SchoolName Registered. Admin: $Name. Creds sent via Email.',
                'available_tags' => '$Name, $SchoolName, $Email, $Password',
                'is_active' => true,
            ],

            // General Fallback User Welcome
            [
                'event_key' => 'user_welcome',
                'name' => 'General User Welcome',
                'body' => 'Welcome to $SchoolName. Your login credentials are ID: $Email, Password: $Password.',
                'available_tags' => '$Name, $SchoolName, $Email, $Password',
                'is_active' => true,
            ],

            // Guardian / Parent Welcome
            [
                'event_key' => 'guardian_welcome',
                'name' => 'Guardian Welcome',
                'body' => 'Welcome to the Parent Portal of $SchoolName. Login using your Phone/Email. Password: $Password.',
                'available_tags' => '$SchoolName, $Password, $Url',
                'is_active' => true,
            ],
            [
                'event_key' => 'invoice_created',
                'name' => 'Invoice Generated',
                'body' => 'Dear Parent, invoice #$InvoiceNumber of $Amount for $StudentName is due on $DueDate. Please pay on time. Thank you, $SchoolName.',
                'available_tags' => '$StudentName, $Amount, $InvoiceNumber, $DueDate, $SchoolName',
                'is_active' => true,
            ],
            // System Alerts
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
        
        // Output confirmation to console
        $this->command->info('Updated SMS Templates seeded successfully.');
    }
}