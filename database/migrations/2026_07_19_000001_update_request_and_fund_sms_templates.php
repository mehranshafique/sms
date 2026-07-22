<?php

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'fund_request_submitted' => [
                'body' => 'Hi $Requester, fund request $TicketNumber ($Amount) submitted. Pending approval. — $SchoolName',
                'available_tags' => '$Requester, $Title, $Amount, $TicketNumber, $SchoolName',
            ],
            'fund_request_processed' => [
                'body' => 'Hi $Requester, fund request $TicketNumber ($Amount) is $Status. — $SchoolName',
                'available_tags' => '$Requester, $Title, $Status, $Amount, $Remaining, $TicketNumber, $Reason, $SchoolName',
            ],
            'request_submitted' => [
                'body' => 'New request from $StudentName ($Class, $SchoolYear). Type: $RequestType. Ticket: $TicketNumber. — $SchoolName',
                'available_tags' => '$StudentName, $RequestType, $TicketNumber, $SchoolName, $SchoolYear, $Class',
            ],
            'request_submitted_parent' => [
                'body' => 'Ticket #$TicketNumber has been opened for $StudentName exemption request. The student is enrolled in $Class for the $SchoolYear academic year. The request covers a period of $Days days. We will get back to you within $ResponseTime. — $SchoolName',
                'available_tags' => '$StudentName, $TicketNumber, $RequestType, $ResponseTime, $SchoolName, $SchoolYear, $Class, $Days',
            ],
        ];

        foreach ($updates as $eventKey => $payload) {
            SmsTemplate::whereNull('institution_id')
                ->where('event_key', $eventKey)
                ->update($payload);

            // Ensure global row exists if missing
            SmsTemplate::firstOrCreate(
                ['institution_id' => null, 'event_key' => $eventKey],
                array_merge($payload, [
                    'name' => match ($eventKey) {
                        'fund_request_submitted' => 'Fund Request Submitted',
                        'fund_request_processed' => 'Fund Request Processed',
                        'request_submitted' => 'New Student Request Submitted',
                        'request_submitted_parent' => 'Parent Request / Derogation Confirmation',
                        default => $eventKey,
                    },
                    'is_active' => true,
                ])
            );
        }
    }

    public function down(): void
    {
        // Non-destructive: keep updated template bodies.
    }
};
