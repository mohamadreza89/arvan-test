<?php

namespace App\Channels;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LogChannel
{
    public function send($notifiable, Notification $notification)
    {
        /** @var MailMessage $message */
        $message = ($notification->toMail($notifiable));

        Log::info($message->toArray());
    }

}