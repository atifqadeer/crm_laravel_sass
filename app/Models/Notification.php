<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'sale_id',
        'applicant_id',
        'type',
        'message',
        'status',
        'notify_by',   // 👈 added here
    ];
}
