<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class DialCallLog extends Model
{
    protected $table = 'dial_call_logs';

    protected $fillable = [
        'phone_key',
        'user_id',
        'call_date',
        'calls',
    ];

    protected $casts = [
        'call_date' => 'date',
        'user_id'   => 'integer',
        'calls'     => 'integer',
    ];
}
