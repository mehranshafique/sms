<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserWelcomeMail;

class SendUserWelcomeEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $passwordPlain;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, $passwordPlain)
    {
        $this->user = $user;
        $this->passwordPlain = $passwordPlain;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)
            ->send(new UserWelcomeMail($this->user, $this->passwordPlain));
    }
}
