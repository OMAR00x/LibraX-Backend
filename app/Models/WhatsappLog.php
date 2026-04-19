<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phone',
        'message',
        'status',
        'provider',
        'provider_message_id',
        'error_message',
        'otp',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
