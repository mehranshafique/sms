<?php

namespace App\Services\Sms;

use App\Interfaces\SmsGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobishastraService implements SmsGatewayInterface
{
    protected $user;
    protected $pwd;
    protected $senderId;

    public function __construct()
    {
        $this->user = config('sms.mobishastra.user');
        $this->pwd = config('sms.mobishastra.password');
        $this->senderId = config('sms.mobishastra.sender_id');
    }

    public function send(string $to, string $message): bool
    {
        try {
            // Based on Mobishastra API documentation
            $response = Http::get('http://mshastra.com/sendurl.aspx', [
                'user' => $this->user,
                'pwd' => $this->pwd,
                'senderid' => $this->senderId,
                'mobileno' => $to,
                'msgtext' => $message,
                'priority' => 'High',
                'CountryCode' => 'ALL'
            ]);

            if ($response->successful() && str_contains($response->body(), 'Success')) {
                return true;
            }

            Log::error('Mobishastra SMS Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('Mobishastra Exception: ' . $e->getMessage());
            return false;
        }
    }
}