<?php

namespace App\Interfaces;

interface SmsGatewayInterface
{
    /**
     * Send an SMS message.
     *
     * @param string $to The phone number (international format)
     * @param string $message The message content
     * @return bool True if sent successfully
     */
    public function send(string $to, string $message): array;
}