<?php

namespace Horsefly;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $table = 'sms_templates';
    protected $fillable = [
        'title',
        'template',
        'status'
    ];
}
