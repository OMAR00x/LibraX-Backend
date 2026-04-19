<?php

namespace App\Channels;

use App\Services\FirebaseNotificationService;
use Illuminate\Notifications\Notification;

class FirebaseChannel
{
    protected $firebase;

    public function __construct(FirebaseNotificationService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function send($notifiable, Notification $notification)
    {
        if (!$notifiable->fcm_token) {
            return;
        }

        $data = $notification->toFirebase($notifiable);
        
        $this->firebase->sendToToken(
            $notifiable->fcm_token,
            $data['title'],
            $data['body'],
            $data['data'] ?? []
        );
    }
}
