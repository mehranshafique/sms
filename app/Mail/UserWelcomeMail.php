<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $passwordPlain;

    public function __construct(User $user, $passwordPlain)
    {
        $this->user = $user;
        $this->passwordPlain = $passwordPlain;
    }

    public function build()
    {
        return $this->subject('Welcome to the System')
            ->view('emails.user-welcome');
    }
}
