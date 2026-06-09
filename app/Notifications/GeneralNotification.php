<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel;

class GeneralNotification extends Notification
{
    use Queueable;

    protected $type;
    protected $titleAr;
    protected $titleEn;
    protected $messageAr;
    protected $messageEn;
    protected $extraData;

    public function __construct(string $type, string $titleAr, string $titleEn, string $messageAr, string $messageEn, array $extraData = [])
    {
        $this->type = $type;
        $this->titleAr = $titleAr;
        $this->titleEn = $titleEn;
        $this->messageAr = $messageAr;
        $this->messageEn = $messageEn;
        $this->extraData = $extraData;
    }

    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => $this->type,
            'title_ar' => $this->titleAr,
            'title_en' => $this->titleEn,
            'message_ar' => $this->messageAr,
            'message_en' => $this->messageEn,
            'extra_data' => $this->extraData,
        ];
    }

    public function toFcm($notifiable)
    {
        $tokens = $notifiable->fcmTokens()->pluck('token')->toArray();

        $locale = $notifiable->locale ?? 'ar';
        $title = $locale === 'en' ? $this->titleEn : $this->titleAr;
        $body = $locale === 'en' ? $this->messageEn : $this->messageAr;

        return [
            'tokens' => $tokens,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => array_merge([
                'type' => $this->type,
                'title_ar' => $this->titleAr,
                'title_en' => $this->titleEn,
                'message_ar' => $this->messageAr,
                'message_en' => $this->messageEn,
            ], collect($this->extraData)->map(fn($v) => (string)$v)->toArray()),
        ];
    }
}
