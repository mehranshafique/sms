<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function build()
    {
        $schoolName = $this->payment->invoice->institution->name ?? 'School';
        
        return $this->subject("Payment Receipt - {$schoolName}")
                    ->view('emails.payment_received');
    }
}