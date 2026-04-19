<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;

class FcmChannel
{
    public function send($notifiable, Notification $notification)
    {
        $fcmData = $notification->toFcm($notifiable);

        \Log::info('FCM Channel - Attempting to send', [
            'notifiable_id' => $notifiable->id,
            'notification' => get_class($notification),
            'fcmData' => $fcmData
        ]);

        if (!$fcmData || empty($fcmData['tokens'])) {
            \Log::warning('FCM Channel - No tokens found', [
                'notifiable_id' => $notifiable->id,
                'fcmData' => $fcmData
            ]);
            return;
        }

        try {
            $credentialsPath = config('firebase.credentials');

            if (!file_exists($credentialsPath)) {
                \Log::error('Firebase credentials file not found', ['path' => $credentialsPath]);
                return;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $messaging = $factory->createMessaging();

            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $fcmData['notification']['title'] ?? '',
                    'body' => $fcmData['notification']['body'] ?? '',
                ])
                ->withData($fcmData['data'] ?? [])
                ->withAndroidConfig([
                    'priority' => 'high',
                    'notification' => [
                        'channelId' => 'librax_notifications',
                        'sound' => 'notification_sound',
                        'color' => '#FF6B35',
                    ],
                ]);


            // Send to first token only
            $tokens = $fcmData['tokens'];
            $successCount = 0;

            foreach ($tokens as $token) {
                try {
                    $result = $messaging->send($message->withChangedTarget('token', $token));
                    \Log::info('FCM sent successfully', ['token' => substr($token, 0, 20) . '...']);
                    $successCount++;
                    break; // نجح الإرسال، توقف
                } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
                    \Log::warning('FCM token not found, deleting', ['token' => substr($token, 0, 20) . '...']);
                    \App\Models\FcmToken::where('token', $token)->delete();
                } catch (\Exception $e) {
                    \Log::error('FCM send error: ' . $e->getMessage(), ['token' => substr($token, 0, 20) . '...']);
                }
            }

            if ($successCount === 0) {
                \Log::warning('Failed to send FCM to any token', ['user_id' => $notifiable->id]);
            }
        } catch (\Exception $e) {
            \Log::error('FCM error: ' . $e->getMessage());
        }
    }
}
