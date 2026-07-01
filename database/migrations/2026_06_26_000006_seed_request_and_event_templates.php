<?php

use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $sms = [
            [
                'event_key' => 'request_submitted_parent',
                'name' => 'Parent Request Confirmation',
                'body' => 'Dear parent, we received a $RequestType request for $StudentName. Ticket: $TicketNumber. We will respond within $ResponseTime. — $SchoolName',
                'available_tags' => '$StudentName, $TicketNumber, $RequestType, $ResponseTime, $SchoolName',
            ],
            [
                'event_key' => 'derogation_reminder',
                'name' => 'Derogation Payment Reminder',
                'body' => 'Reminder: fee extension for $StudentName (Ticket $TicketNumber) expires on $Deadline. Please pay to avoid penalties. — $SchoolName',
                'available_tags' => '$StudentName, $TicketNumber, $Deadline, $SchoolName',
            ],
            [
                'event_key' => 'derogation_expired',
                'name' => 'Derogation Expired',
                'body' => 'The fee extension for $StudentName (Ticket $TicketNumber) has expired. Contact the school office. — $SchoolName',
                'available_tags' => '$StudentName, $TicketNumber, $SchoolName',
            ],
            [
                'event_key' => 'derogation_honored',
                'name' => 'Derogation Honored',
                'body' => 'Thank you! The fee extension for $StudentName (Ticket $TicketNumber) has been honored. — $SchoolName',
                'available_tags' => '$StudentName, $TicketNumber, $SchoolName',
            ],
            [
                'event_key' => 'otp_login',
                'name' => 'Login OTP',
                'body' => 'Your login code for $SchoolName is $OTP. Valid for 10 minutes.',
                'available_tags' => '$OTP, $Name, $SchoolName',
            ],
            [
                'event_key' => 'event_invitation',
                'name' => 'School Event Invitation',
                'body' => 'Dear $ParentName, you are invited to $EventName on $EventDate at $EventTime. Venue: $Venue. Student: $StudentName ($ClassName). Ref: $TicketNumber. — $SchoolName',
                'available_tags' => '$ParentName, $StudentName, $ClassName, $EventName, $EventDate, $EventTime, $Venue, $TicketNumber, $SchoolName',
            ],
            [
                'event_key' => 'agent_payment_processed',
                'name' => 'Agent Payment Processed',
                'body' => 'Hello $AgentName, your commission payment of $Amount for $Period has been processed. — $SchoolName',
                'available_tags' => '$AgentName, $Amount, $Period, $SchoolName',
            ],
        ];

        foreach ($sms as $row) {
            SmsTemplate::firstOrCreate(
                ['institution_id' => null, 'event_key' => $row['event_key']],
                array_merge($row, ['is_active' => true])
            );
        }

        $emails = [
            [
                'event_key' => 'request_updated',
                'name' => 'Request Status Update',
                'subject' => 'Ticket $TicketNumber — $Status',
                'body' => 'Dear parent,' . "\n\n" . 'Your request for $StudentName has been updated.' . "\n" . 'Ticket: $TicketNumber' . "\n" . 'Type: $RequestType' . "\n" . 'Status: $Status' . "\n" . 'Note: $AdminNote' . "\n\n" . '$SchoolName',
                'available_tags' => '$TicketNumber, $StudentName, $RequestType, $Status, $AdminNote, $SchoolName',
            ],
            [
                'event_key' => 'event_invitation',
                'name' => 'Event Invitation Email',
                'subject' => 'Invitation: $EventName',
                'body' => 'Dear $ParentName,' . "\n\n" . 'You are invited to $EventName on $EventDate at $EventTime.' . "\n" . 'Venue: $Venue' . "\n" . 'Student: $StudentName ($ClassName)' . "\n" . 'Reference: $TicketNumber' . "\n\n" . '$SchoolName',
                'available_tags' => '$ParentName, $StudentName, $ClassName, $EventName, $EventDate, $EventTime, $Venue, $TicketNumber, $SchoolName',
            ],
        ];

        foreach ($emails as $row) {
            EmailTemplate::firstOrCreate(
                ['institution_id' => null, 'event_key' => $row['event_key']],
                array_merge($row, ['is_active' => true])
            );
        }
    }

    public function down(): void
    {
        SmsTemplate::whereNull('institution_id')->whereIn('event_key', [
            'request_submitted_parent', 'derogation_reminder', 'derogation_expired', 'derogation_honored', 'otp_login', 'event_invitation', 'agent_payment_processed',
        ])->delete();

        EmailTemplate::whereNull('institution_id')->whereIn('event_key', ['request_updated', 'event_invitation'])->delete();
    }
};
