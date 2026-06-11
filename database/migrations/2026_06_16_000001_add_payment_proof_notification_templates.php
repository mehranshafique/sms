<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\SmsTemplate;

return new class extends Migration
{
    public function up(): void
    {
        $templates = [
            [
                'event_key' => 'payment_proof_submitted',
                'name' => 'Payment Proof Submitted',
                'body' => 'Dear Parent, we received your payment proof of $Amount for $StudentName (Invoice $InvoiceNumber). Our accounts team will review it shortly. — $SchoolName',
                'available_tags' => '$StudentName, $Amount, $InvoiceNumber, $SchoolName, $PayerName',
                'is_active' => true,
            ],
            [
                'event_key' => 'payment_proof_rejected',
                'name' => 'Payment Proof Rejected',
                'body' => 'Dear Parent, your payment proof for $StudentName (Invoice $InvoiceNumber, $Amount) could not be verified. Please resubmit or contact the school. — $SchoolName',
                'available_tags' => '$StudentName, $Amount, $InvoiceNumber, $SchoolName, $Reason',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            SmsTemplate::firstOrCreate(
                ['event_key' => $template['event_key'], 'institution_id' => null],
                $template
            );
        }
    }

    public function down(): void
    {
        SmsTemplate::whereIn('event_key', ['payment_proof_submitted', 'payment_proof_rejected'])
            ->whereNull('institution_id')
            ->delete();
    }
};
