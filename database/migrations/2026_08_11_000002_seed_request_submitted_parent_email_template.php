<?php

use App\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        EmailTemplate::firstOrCreate(
            ['institution_id' => null, 'event_key' => 'request_submitted_parent'],
            [
                'name' => 'Parent Request Confirmation',
                'subject' => 'Request received — Ticket $TicketNumber',
                'body' => "Dear parent,\n\nWe received a \$RequestType request for \$StudentName.\nTicket: \$TicketNumber\nWe will respond within \$ResponseTime.\n\n\$SchoolName",
                'available_tags' => '$StudentName, $TicketNumber, $RequestType, $ResponseTime, $SchoolName, $SchoolYear, $Class, $Days',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        EmailTemplate::whereNull('institution_id')
            ->where('event_key', 'request_submitted_parent')
            ->delete();
    }
};
