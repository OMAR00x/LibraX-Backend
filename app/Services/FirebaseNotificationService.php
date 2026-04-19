<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseNotificationService
{
    protected $messaging;

    public function __construct()
    {
        $credentialsPath = config('firebase.credentials');

        if (!file_exists($credentialsPath)) {
            throw new \Exception("Firebase credentials file not found at: {$credentialsPath}");
        }

        $factory = (new Factory)->withServiceAccount($credentialsPath);
        $this->messaging = $factory->createMessaging();
    }

    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            \Log::error('Firebase notification error: ' . $e->getMessage());
            return false;
        }
    }

    public function sendToMultipleTokens(array $tokens, string $title, string $body, array $data = []): array
    {
        $results = [];
        foreach ($tokens as $token) {
            $results[$token] = $this->sendToToken($token, $title, $body, $data);
        }
        return $results;
    }

    /**
     * إرسال إشعار فوري لمستخدم محدد
     */
    public function sendToUser(int $userId, string $title, string $body, array $data = []): bool
    {
        try {
            $tokens = \App\Models\FcmToken::where('user_id', $userId)->pluck('token')->toArray();

            if (empty($tokens)) {
                \Log::warning('No FCM tokens found for user', ['userId' => $userId]);
                return false;
            }

            $message = CloudMessage::new()
                ->withNotification([
                    'title' => $title,
                    'body' => $body,
                ])
                ->withData($data)
                ->withAndroidConfig([
                    'priority' => 'high',
                    'notification' => [
                        'channelId' => 'librax_notifications',
                        'sound' => 'notification_sound',
                        'color' => '#FF6B35',
                    ],
                ]);

            foreach ($tokens as $token) {
                try {
                    $this->messaging->send($message->withChangedTarget('token', $token));
                    \Log::info('FCM sent to user', ['userId' => $userId, 'token' => substr($token, 0, 20)]);
                } catch (\Exception $e) {
                    \Log::error('FCM send failed', ['token' => substr($token, 0, 20), 'error' => $e->getMessage()]);
                    // حذف التوكن الفاشل
                    \App\Models\FcmToken::where('token', $token)->delete();
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Firebase sendToUser error: ' . $e->getMessage());
            return false;
        }
    }
}
