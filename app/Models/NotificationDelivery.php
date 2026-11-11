<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationDelivery extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['notification_type', 'channel', 'notifiable_type', 'notifiable_id', 'status', 'error'];
}
